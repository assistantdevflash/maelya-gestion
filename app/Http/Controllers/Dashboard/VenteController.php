<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\CodeReduction;
use App\Models\MouvementStock;
use App\Models\HistoriquePoints;
use App\Models\ProgrammeFidelite;
use App\Models\Prestation;
use App\Models\Produit;
use App\Models\Vente;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VenteController extends Controller
{
    private function institutId(): string
    {
        return session('current_institut_id', Auth::user()->institut_id);
    }

    public function caisse()
    {
        $clients = Client::where('actif', true)->orderBy('prenom')->get();
        return view('dashboard.caisse.index', compact('clients'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'panier_json' => ['required', 'string'],
            'client_id' => ['nullable', 'string'],
            'mode_paiement' => ['required', 'in:cash,carte,mobile_money,mixte'],
            'reference_paiement' => ['nullable', 'string', 'max:100'],
            'total' => ['required', 'numeric', 'min:0'],
            'remise' => ['nullable', 'integer', 'min:0'],
            'code_reduction_id' => ['nullable', 'string'],
            'imprimer' => ['nullable', 'string'],
            'montant_cash' => ['nullable', 'integer', 'min:0'],
            'montant_mobile' => ['nullable', 'integer', 'min:0'],
            'montant_carte' => ['nullable', 'integer', 'min:0'],
        ]);

        $items = json_decode($request->panier_json, true);

        if (!is_array($items) || empty($items)) {
            return back()->withErrors(['panier_json' => 'Le panier est vide.']);
        }

        $vente = DB::transaction(function () use ($items, $request) {
            $totalBrut = 0;
            $itemsToSave = [];

            // Valider que le client appartient à cet institut
            if ($request->filled('client_id')) {
                $client = Client::find($request->client_id);
                abort_unless($client && $client->institut_id === $this->institutId(), 403, 'Client invalide.');
            }

            foreach ($items as $item) {
                if ($item['type'] === 'prestation') {
                    $model = Prestation::findOrFail($item['id']);
                    $prix = $model->prix;
                    $nom = $model->nom;
                } else {
                    $model = Produit::findOrFail($item['id']);
                    $prix = $model->prix_vente;
                    $nom = $model->nom;
                }

                $quantite = (int) ($item['quantite'] ?? 1);
                $sousTotal = $prix * $quantite;
                $totalBrut += $sousTotal;

                $itemsToSave[] = [
                    'type' => $item['type'],
                    'item_id' => $item['id'],
                    'nom_snapshot' => $nom,
                    'prix_snapshot' => $prix,
                    'quantite' => $quantite,
                    'sous_total' => $sousTotal,
                ];
            }

            // Valider et appliquer le code de réduction côté serveur
            $remise = 0;
            $codeReductionId = null;
            if ($request->filled('code_reduction_id')) {
                $code = CodeReduction::where('institut_id', $this->institutId())
                    ->find($request->code_reduction_id);
                if ($code && !$code->validerPourTotal((int) $totalBrut, $request->client_id ?: null)) {
                    $remise = $code->calculerRemise((int) $totalBrut);
                    $codeReductionId = $code->id;
                    $code->increment('nb_utilisations');
                }
            }

            $total = max(0, $totalBrut - $remise);

            $vente = Vente::create([
                'institut_id' => $this->institutId(),
                'client_id' => $request->client_id ?: null,
                'user_id' => Auth::id(),
                'total' => $total,
                'remise' => $remise,
                'code_reduction_id' => $codeReductionId,
                'mode_paiement' => $request->mode_paiement,
                'reference_paiement' => $request->reference_paiement,
                'montant_cash' => match($request->mode_paiement) {
                    'cash'  => $total,
                    'mixte' => (int) $request->montant_cash,
                    default => 0,
                },
                'montant_mobile' => match($request->mode_paiement) {
                    'mobile_money' => $total,
                    'mixte'        => (int) $request->montant_mobile,
                    default        => 0,
                },
                'montant_carte' => match($request->mode_paiement) {
                    'carte' => $total,
                    'mixte' => (int) $request->montant_carte,
                    default => 0,
                },
                'statut' => 'validee',
                'ip_address' => request()->ip(),
            ]);

            foreach ($itemsToSave as $item) {
                $item['id'] = (string) Str::uuid();
                $vente->items()->create($item);

                // Décrémenter le stock pour les produits
                if ($item['type'] === 'produit') {
                    $produit = Produit::find($item['item_id']);
                    if ($produit) {
                        $stockAvant = $produit->stock;
                        $produit->decrement('stock', $item['quantite']);

                        MouvementStock::create([
                            'institut_id' => $this->institutId(),
                            'produit_id' => $produit->id,
                            'user_id' => Auth::id(),
                            'vente_id' => $vente->id,
                            'type' => 'sortie_vente',
                            'quantite' => $item['quantite'],
                            'stock_avant' => $stockAvant,
                            'stock_apres' => max(0, $stockAvant - $item['quantite']),
                        ]);
                    }
                }
            }

            // ── Attribuer les points de fidélité ──
            if ($vente->client_id) {
                $programme = ProgrammeFidelite::where('institut_id', $this->institutId())
                    ->where('actif', true)
                    ->first();

                if ($programme) {
                    $points = $programme->calculerPoints($total);
                    if ($points > 0) {
                        $vente->client->increment('points_fidelite', $points);

                        HistoriquePoints::create([
                            'institut_id' => $this->institutId(),
                            'client_id' => $vente->client_id,
                            'vente_id' => $vente->id,
                            'points' => $points,
                            'type' => 'gain',
                            'description' => "+{$points} pts (vente #{$vente->numero})",
                        ]);
                    }
                }
            }

            return $vente;
        });

        if ($request->imprimer) {
            // Le formulaire a été soumis en target=_blank,
            // donc ce redirect ouvre le PDF dans le nouvel onglet
            return redirect()->route('dashboard.ventes.ticket-pdf', $vente);
        }

        return redirect()->route('dashboard.caisse')
            ->with('success', 'Vente #' . substr($vente->id, 0, 8) . ' enregistrée — ' . number_format($vente->total, 0, ',', ' ') . ' F');
    }

    public function index(Request $request)
    {
        $isEmploye = Auth::user()->isEmploye();

        // Membres de l'équipe pour le filtre vendeur (admin seulement)
        $membres = collect();
        $statsParEmploye = collect();

        if (!$isEmploye) {
            $institutId = $this->institutId();

            // Employés de l'établissement courant
            $employes = \App\Models\User::where('institut_id', $institutId)
                ->where('role', 'employe')
                ->orderBy('prenom')
                ->get(['id', 'prenom', 'nom_famille', 'role']);

            // Propriétaire de l'établissement courant
            $proprietaire = \App\Models\Institut::find($institutId)?->proprietaire;
            $membres = $employes;
            if ($proprietaire && !$membres->contains('id', $proprietaire->id)) {
                $membres->prepend($proprietaire);
            }

            // Stats agrégées par vendeur sur la période filtrée (hors pagination)
            $statsParEmploye = Vente::with('user:id,prenom,nom_famille,role')
                ->selectRaw("user_id, COUNT(*) as nb_ventes, SUM(CASE WHEN statut != 'annulee' THEN total ELSE 0 END) as total_ventes")
                ->when($request->debut, fn($q) => $q->whereDate('created_at', '>=', $request->debut))
                ->when($request->fin, fn($q) => $q->whereDate('created_at', '<=', $request->fin))
                ->when($request->mode, fn($q) => $q->where('mode_paiement', $request->mode))
                ->groupBy('user_id')
                ->orderByDesc('total_ventes')
                ->get();
        }

        $ventes = Vente::with('client', 'user', 'items', 'codeReduction')
            ->when($isEmploye, fn($q) => $q->where('user_id', Auth::id()))
            ->when($request->debut, fn($q) => $q->whereDate('created_at', '>=', $request->debut))
            ->when($request->fin, fn($q) => $q->whereDate('created_at', '<=', $request->fin))
            ->when($request->mode, fn($q) => $q->where('mode_paiement', $request->mode))
            ->when(!$isEmploye && $request->employe_id, fn($q) => $q->where('user_id', $request->employe_id))
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('dashboard.caisse.historique', compact('ventes', 'membres', 'statsParEmploye'));
    }

    public function show(Vente $vente)
    {
        if (Auth::user()->isEmploye() && $vente->user_id !== Auth::id()) {
            abort(403);
        }

        $vente->load('items', 'client', 'user', 'codeReduction');
        return view('dashboard.caisse.show', compact('vente'));
    }

    public function annuler(Vente $vente)
    {
        if (Auth::user()->isEmploye() && $vente->user_id !== Auth::id()) {
            abort(403);
        }

        if ($vente->statut === 'annulee') {
            return back()->with('error', 'Cette vente est déjà annulée.');
        }

        DB::transaction(function () use ($vente) {
            foreach ($vente->items as $item) {
                if ($item->type === 'produit') {
                    $produit = Produit::withoutGlobalScopes()->find($item->item_id);
                    if ($produit) {
                        $stockAvant = $produit->stock;
                        $produit->increment('stock', $item->quantite);

                        MouvementStock::create([
                            'institut_id' => $this->institutId(),
                            'produit_id' => $produit->id,
                            'user_id' => Auth::id(),
                            'vente_id' => $vente->id,
                            'type' => 'annulation_vente',
                            'quantite' => $item->quantite,
                            'stock_avant' => $stockAvant,
                            'stock_apres' => $stockAvant + $item->quantite,
                        ]);
                    }
                }
            }

            $vente->update(['statut' => 'annulee']);
        });

        return back()->with('success', 'Vente annulée et stock restauré.');
    }

    public function ticketPdf(Vente $vente)
    {
        if (Auth::user()->isEmploye() && $vente->user_id !== Auth::id()) {
            abort(403);
        }

        $vente->load('items', 'client', 'institut', 'user');
        $pdf = Pdf::loadView('pdf.ticket', compact('vente'))
            ->setPaper([0, 0, 226.77, 600], 'portrait');
        return $pdf->download("ticket-{$vente->numero}.pdf");
    }
}
