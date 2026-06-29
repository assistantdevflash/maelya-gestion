<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\BonCommande;
use App\Models\BonCommandeLigne;
use App\Models\Fournisseur;
use App\Models\MouvementStock;
use App\Models\Produit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class BonCommandeController extends Controller
{
    private function institutId(): string
    {
        return session('current_institut_id', Auth::user()->institut_id);
    }

    public function index()
    {
        $bons = BonCommande::with('fournisseur')->latest()->paginate(30);
        return view('dashboard.bons-commande.index', compact('bons'));
    }

    public function create()
    {
        $fournisseurs = Fournisseur::where('actif', true)->orderBy('nom')->get();
        $produits = Produit::where('actif', true)->orderBy('nom')->get(['id', 'nom', 'unite', 'prix_achat']);
        return view('dashboard.bons-commande.create', compact('fournisseurs', 'produits'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'fournisseur_id'        => ['nullable', 'uuid', 'exists:fournisseurs,id'],
            'date_commande'         => ['required', 'date'],
            'date_livraison_prevue' => ['nullable', 'date'],
            'notes'                 => ['nullable', 'string'],
            'lignes'                => ['required', 'array', 'min:1'],
            'lignes.*.produit_id'   => ['nullable', 'uuid'],
            'lignes.*.libelle'      => ['required', 'string', 'max:200'],
            'lignes.*.quantite'     => ['required', 'integer', 'min:1'],
            'lignes.*.prix'         => ['required', 'integer', 'min:0'],
        ]);

        $bon = DB::transaction(function () use ($data) {
            $bon = BonCommande::create([
                'fournisseur_id'        => $data['fournisseur_id'] ?? null,
                'user_id'               => Auth::id(),
                'date_commande'         => $data['date_commande'],
                'date_livraison_prevue' => $data['date_livraison_prevue'] ?? null,
                'statut'                => 'brouillon',
                'notes'                 => $data['notes'] ?? null,
                'total_ht'              => 0,
            ]);

            $total = 0;
            foreach ($data['lignes'] as $l) {
                $sousTotal = $l['quantite'] * $l['prix'];
                $total += $sousTotal;
                BonCommandeLigne::create([
                    'bon_commande_id'    => $bon->id,
                    'produit_id'         => $l['produit_id'] ?? null,
                    'libelle'            => $l['libelle'],
                    'quantite_commandee' => $l['quantite'],
                    'prix_unitaire'      => $l['prix'],
                    'sous_total'         => $sousTotal,
                ]);
            }
            $bon->update(['total_ht' => $total]);
            return $bon;
        });

        return redirect()->route('dashboard.bons-commande.show', $bon)->with('success', 'Bon de commande créé.');
    }

    public function show(BonCommande $bonsCommande)
    {
        $bonsCommande->load('fournisseur', 'lignes.produit', 'user');
        return view('dashboard.bons-commande.show', ['bon' => $bonsCommande]);
    }

    /** Marquer comme envoyé */
    public function envoyer(BonCommande $bonsCommande)
    {
        if ($bonsCommande->statut === 'brouillon') {
            $bonsCommande->update(['statut' => 'envoye']);
        }
        return back()->with('success', 'Bon de commande envoyé.');
    }

    /** Réceptionner la marchandise (incrémente le stock + recalcule CMP) */
    public function recevoir(Request $request, BonCommande $bonsCommande)
    {
        $data = $request->validate([
            'recus'   => ['required', 'array'],
            'recus.*' => ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($bonsCommande, $data) {
            $allRecu = true;
            foreach ($bonsCommande->lignes as $ligne) {
                $qteRecue = (int) ($data['recus'][$ligne->id] ?? 0);
                if ($qteRecue <= 0) {
                    if ($ligne->quantite_recue < $ligne->quantite_commandee) $allRecu = false;
                    continue;
                }
                $nouvelleQteRecue = min($ligne->quantite_commandee, $ligne->quantite_recue + $qteRecue);
                $delta = $nouvelleQteRecue - $ligne->quantite_recue;
                if ($delta <= 0) continue;

                if ($ligne->produit_id) {
                    $produit = Produit::find($ligne->produit_id);
                    if ($produit) {
                        $produit->recalculerCmp($delta, $ligne->prix_unitaire);
                        $stockAvant = $produit->stock;
                        $produit->increment('stock', $delta);

                        MouvementStock::create([
                            'institut_id'   => $bonsCommande->institut_id,
                            'produit_id'    => $produit->id,
                            'user_id'       => Auth::id(),
                            'type'          => 'entree',
                            'quantite'      => $delta,
                            'prix_unitaire' => $ligne->prix_unitaire,
                            'stock_avant'   => $stockAvant,
                            'stock_apres'   => $stockAvant + $delta,
                            'note'          => "Réception {$bonsCommande->numero}",
                        ]);
                    }
                }
                $ligne->update(['quantite_recue' => $nouvelleQteRecue]);
                if ($nouvelleQteRecue < $ligne->quantite_commandee) $allRecu = false;
            }

            $bonsCommande->update(['statut' => $allRecu ? 'recu' : 'recu_partiel']);
        });

        return back()->with('success', 'Réception enregistrée et stock mis à jour.');
    }

    public function annuler(BonCommande $bonsCommande)
    {
        $bonsCommande->update(['statut' => 'annule']);
        return back()->with('success', 'Bon de commande annulé.');
    }

    /** Télécharger le bon de commande en PDF */
    public function pdf(BonCommande $bonsCommande)
    {
        $bonsCommande->load('fournisseur', 'lignes.produit', 'user', 'institut');
        $pdf = Pdf::loadView('pdf.bon-commande', ['bon' => $bonsCommande])
            ->setPaper('a4', 'portrait');
        return $pdf->download("bon-commande-{$bonsCommande->numero}.pdf");
    }

    /** Envoyer le bon de commande par email au fournisseur */
    public function envoyerEmail(BonCommande $bonsCommande)
    {
        if (!$bonsCommande->fournisseur?->email) {
            return back()->with('error', 'Ce fournisseur n\'a pas d\'adresse email.');
        }

        $bonsCommande->load('fournisseur', 'lignes.produit', 'user', 'institut');

        $institut = $bonsCommande->institut;
        $pdf = Pdf::loadView('pdf.bon-commande', ['bon' => $bonsCommande])
            ->setPaper('a4', 'portrait');

        Mail::send('emails.bon-commande', ['bon' => $bonsCommande], function ($message) use ($bonsCommande, $institut, $pdf) {
            $message->to($bonsCommande->fournisseur->email, $bonsCommande->fournisseur->contact_principal ?: $bonsCommande->fournisseur->nom)
                    ->subject('Bon de commande ' . $bonsCommande->numero . ' — ' . ($institut?->nom ?? 'Maelya Gestion'))
                    ->attachData($pdf->output(), "bon-commande-{$bonsCommande->numero}.pdf", ['mime' => 'application/pdf']);
        });

        // Si encore en brouillon, passer à envoyé
        if ($bonsCommande->statut === 'brouillon') {
            $bonsCommande->update(['statut' => 'envoye']);
        }

        return back()->with('success', 'Bon de commande envoyé par email à ' . $bonsCommande->fournisseur->nom . '.');
    }
}
