<?php
namespace App\Http\Controllers\Dashboard;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Facture;
use App\Models\FactureItem;
use App\Models\FactureRelance;
use App\Models\Paiement;
use App\Mail\FactureARelancer;
use App\Services\DevisService;
use App\Services\FactureService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class FactureController extends Controller
{
    public function index(Request $request)
    {
        $institutId = session('current_institut_id', Auth::user()->institut_id);
        $statut     = $request->input('statut', '');
        $search     = trim((string) $request->input('search', ''));
        $retard     = $request->boolean('retard');

        $query = Facture::where('institut_id', $institutId)->with(['client','devis'])->latest('date_emission');

        if ($statut !== '') $query->where('statut', $statut);
        if ($retard) {
            $query->whereNotIn('statut', ['payee', 'annulee'])
                  ->whereDate('date_echeance', '<', now()->toDateString());
        }
        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $q->where('numero','like',"%{$search}%")
                  ->orWhere('client_nom','like',"%{$search}%")
                  ->orWhere('client_prenom','like',"%{$search}%")
                  ->orWhere('client_telephone','like',"%{$search}%");
            });
        }

        $factures = $query->paginate(20)->withQueryString();
        $stats = Cache::remember("facture_stats:{$institutId}", now()->addMinutes(5), function () use ($institutId) {
            $row = Facture::where('institut_id', $institutId)
                ->selectRaw(
                    "COALESCE(SUM(CASE WHEN statut <> 'annulee' THEN total_ttc ELSE 0 END),0) as total_ttc,"
                    . " COALESCE(SUM(CASE WHEN statut <> 'annulee' THEN montant_paye ELSE 0 END),0) as total_paye,"
                    . " SUM(CASE WHEN statut NOT IN ('payee','annulee') AND date_echeance < ? THEN 1 ELSE 0 END) as en_retard",
                    [now()->toDateTimeString()]
                )
                ->first();
            return ['total_ttc' => (int) $row->total_ttc, 'total_paye' => (int) $row->total_paye, 'en_retard' => (int) $row->en_retard];
        });
        return view('dashboard.devis-factures.index', compact('factures','stats','statut','search','retard'))->with('tab','factures');
    }

    public function create(Request $request)
    {
        $institutId = session('current_institut_id', Auth::user()->institut_id);
        $allClients = Client::where('institut_id', $institutId)
            ->orderBy('nom')
            ->get()
            ->map(fn($c) => [
                'id'        => $c->id,
                'prenom'    => $c->prenom,
                'nom'       => $c->nom,
                'nom_affichage' => $c->nom_affichage,
                'telephone' => $c->telephone,
                'email'     => $c->email,
                'adresse'   => $c->adresse,
                'initiale'  => strtoupper(substr($c->prenom ?: $c->nom, 0, 1)),
                'search'    => strtolower($c->nom . ' ' . $c->prenom . ' ' . $c->telephone),
            ]);

        // Catalogue : prestations + produits existants
        $prestations = \App\Models\Prestation::where('institut_id', $institutId)->where('actif', true)
            ->orderBy('nom')->get(['id', 'nom', 'prix'])
            ->map(fn($p) => ['id' => 'p_'.$p->id, 'type' => 'prestation', 'designation' => $p->nom, 'prix' => $p->prix, 'search' => strtolower($p->nom)]);
        $produits = \App\Models\Produit::where('institut_id', $institutId)->where('actif', true)
            ->orderBy('nom')->get(['id', 'nom', 'prix_vente'])
            ->map(fn($p) => ['id' => 'prod_'.$p->id, 'type' => 'produit', 'designation' => $p->nom, 'prix' => $p->prix_vente, 'search' => strtolower($p->nom)]);
        $catalogue = $prestations->concat($produits)->values();

        $devis = null;
        if ($request->filled('devis_id')) {
            $devis = \App\Models\Devis::with('items')->find($request->devis_id);
        }

        return view('dashboard.devis-factures.factures.create', compact('allClients','devis','catalogue'))
            ->with('duplicateData', session('duplicate_facture'));
    }

    public function store(Request $request)
    {        $data = $request->validate([
            'client_id' => 'nullable|uuid|exists:clients,id',
            'client_prenom' => 'required_without:client_id|string|max:100',
            'client_nom' => 'required_without:client_id|string|max:100',
            'client_email' => 'nullable|email|max:255', 'client_telephone' => 'nullable|string|max:30', 'client_adresse' => 'nullable|string|max:500',
            'date_emission' => 'required|date', 'date_echeance' => 'required|date|after_or_equal:date_emission',
            'notes' => 'nullable|string|max:5000', 'conditions' => 'nullable|string|max:5000',
            'titre' => 'nullable|string|max:200',
            'pied_page_afficher' => 'boolean',
            'pied_page_texte' => 'nullable|string|max:1000',
            'tva_applicable' => 'boolean', 'tva_taux' => 'nullable|numeric|min:0|max:100',
            'remise_globale_type' => 'nullable|in:pourcentage,montant_fixe', 'remise_globale_valeur' => 'nullable|integer|min:0',
            'lignes' => 'required|json',
        ]);
        $lignes = json_decode($data['lignes'], true);
        if (empty($lignes)) return back()->withInput()->withErrors(['lignes' => 'Ajoutez au moins une ligne.']);
        [$sousTotal, $remise, $totalHT, $tva, $totalTTC, $lignes] = DevisService::calculerTotaux($lignes, $data);
        if ($totalTTC <= 0) return back()->withInput()->withErrors(['lignes' => 'Le montant total doit être supérieur à 0 F.']);
        $clientId = $data['client_id'] ?? null;
        if ($clientId) {
            $client = Client::find($clientId);
            if ($client) {
                $data['client_prenom'] = $data['client_prenom'] ?? $client->prenom;
                $data['client_nom'] = $data['client_nom'] ?? $client->nom;
                $data['client_telephone'] = $data['client_telephone'] ?? $client->telephone;
                $data['client_email'] = $data['client_email'] ?? $client->email;
                $data['client_adresse'] = $data['client_adresse'] ?? $client->adresse;
            }
        } elseif (!$clientId && !empty($data['client_telephone'])) {
            // Vérifier si un client existe déjà avec le même téléphone ou email
            $existing = Client::where('institut_id', session('current_institut_id', Auth::user()->institut_id))
                ->where(function ($q) use ($data) {
                    $q->where('telephone', $data['client_telephone']);
                    if (!empty($data['client_email'])) {
                        $q->orWhere('email', $data['client_email']);
                    }
                })->first();
            if ($existing) {
                $clientId = $existing->id;
            } else {
                $client = Client::create([
                    'telephone' => $data['client_telephone'],
                    'institut_id' => session('current_institut_id', Auth::user()->institut_id),
                    'prenom' => $data['client_prenom'] ?? '',
                    'nom' => $data['client_nom'] ?? '',
                    'email' => $data['client_email'] ?? null,
                ]);
                $clientId = $client->id;
            }
        }
        $facture = Facture::create([
            'institut_id' => session('current_institut_id', Auth::user()->institut_id),
            'client_id' => $clientId, 'user_id' => Auth::id(),
            'numero' => FactureService::genererNumero(session('current_institut_id', Auth::user()->institut_id)), 'statut' => 'en_attente',
            'date_emission' => $data['date_emission'], 'date_echeance' => $data['date_echeance'],
            'client_prenom' => $data['client_prenom'] ?? null, 'client_nom' => $data['client_nom'] ?? null,
            'client_email' => $data['client_email'] ?? null, 'client_telephone' => $data['client_telephone'] ?? null, 'client_adresse' => $data['client_adresse'] ?? null,
            'sous_total' => $sousTotal, 'remise_globale_type' => $data['remise_globale_type'] ?? null, 'remise_globale_valeur' => $data['remise_globale_valeur'] ?? 0,
            'total_ht' => $totalHT, 'tva_applicable' => $data['tva_applicable'] ?? false, 'tva_taux' => $data['tva_taux'] ?? 0, 'total_ttc' => $totalTTC,
            'notes' => $data['notes'] ?? null, 'conditions' => $data['conditions'] ?? null, 'titre' => $data['titre'] ?? null, 'token' => Str::random(32),
            'pied_page_afficher' => $data['pied_page_afficher'] ?? true,
            'pied_page_texte' => $data['pied_page_texte'] ?? null,
        ]);
        foreach ($lignes as $i => $ligne) {
            FactureItem::create(array_merge(DevisService::idsCatalogue($ligne), ['facture_id' => $facture->id, 'designation' => $ligne['designation'], 'quantite' => $ligne['quantite'], 'prix_unitaire' => $ligne['prix_unitaire'], 'remise_type' => $ligne['remise_type'] ?? null, 'remise_valeur' => $ligne['remise_valeur'] ?? 0, 'tva_taux' => $ligne['tva_taux'] ?? null, 'total_ligne' => $ligne['total_ligne'], 'ordre' => $i]));
        }
        return redirect()->route('dashboard.factures.show', ['facture' => $facture->id])->with('success','Facture créée.');
    }
    public function show(Facture $facture) { $facture->load(['items','client','devis','paiements.encaisseur','vente','relances','avoirs']); return view('dashboard.devis-factures.factures.show', compact('facture')); }

    public function pdf(Request $request, Facture $facture) {
        $facture->load(['items','client']);
        $pdf = Pdf::loadView('pdf.facture-module', ['facture' => $facture, 'institut' => $facture->institut]);

        // ?inline=1 : le PDF s'affiche dans l'aperçu intégré à la fiche
        return $request->boolean('inline')
            ? $pdf->stream("Facture-{$facture->numero}.pdf")
            : $pdf->download("Facture-{$facture->numero}.pdf");
    }

    /**
     * PDF public (pas d'auth requise) — accès sécurisé par UUID de la facture.
     */
    public function pdfPublic(string $id, ?string $token = null)
    {
        $facture = Facture::where('id', $id)->firstOrFail();

        // Le token est la clé de partage : dès qu'il existe, il est exigé.
        // Les documents créés avant son instauration restent accessibles par UUID.
        if ($facture->token) {
            abort_unless(hash_equals($facture->token, (string) $token), 404);
        }

        $facture->load(['items', 'client']);
        $pdf = Pdf::loadView('pdf.facture-module', ['facture' => $facture, 'institut' => $facture->institut]);
        return $pdf->download("Facture-{$facture->numero}.pdf");
    }

    public function ajouterPaiement(Request $request, Facture $facture)
    {
        if ($facture->statut === 'annulee') {
            return back()->with('error', 'Cette facture est annulée : aucun paiement ne peut y être ajouté.');
        }
        if ($facture->resteAPayer <= 0) {
            return back()->with('error', 'Cette facture est déjà entièrement payée.');
        }

        $data = $request->validate([
            'montant' => 'required|integer|min:1|max:'.$facture->resteAPayer,
            'mode_paiement' => 'required|in:especes,mobile_money,carte,virement,cheque',
            'reference' => 'nullable|string|max:100',
            'date_paiement' => 'required|date',
        ]);
        $paiement = Paiement::create([
            'facture_id' => $facture->id,
            'user_id' => Auth::id(),
            'montant' => $data['montant'],
            'mode_paiement' => $data['mode_paiement'],
            'reference' => $data['reference'] ?? null,
            'date_paiement' => $data['date_paiement'],
        ]);
        $facture->update(['montant_paye' => $facture->paiements()->sum('montant')]);

        // Créer une Vente pour impacter immédiatement le chiffre d'affaires
        // Mapper le mode_paiement vers l'ENUM ventes (cash, mobile_money, mixte)
        $venteMode = match($data['mode_paiement']) {
            'especes', 'virement', 'cheque', 'carte' => 'cash',
            'mobile_money' => 'mobile_money',
            default => 'cash',
        };
        $vente = \App\Models\Vente::create([
            'institut_id' => $facture->institut_id,
            'client_id' => $facture->client_id,
            'user_id' => Auth::id(),
            'total' => $data['montant'],
            'montant_paye' => $data['montant'],
            'mode_paiement' => $venteMode,
            'statut' => 'validee',
        ]);

        // Lier le paiement à la vente générée
        $paiement->update(['vente_id' => $vente->id]);

        if ($facture->fresh()->estPayee) $facture->update(['statut' => 'payee', 'vente_id' => $vente->id]);
        elseif ($facture->montant_paye > 0 && !$facture->fresh()->estPayee) $facture->update(['statut' => 'partiellement_payee']);

        // Invalider les caches (dashboard + KPI de la liste)
        $this->oublierCaches($facture->institut_id);

        return back()->with('success','Paiement enregistré.');
    }

    public function marquerPayee(Facture $facture) {
        if ($facture->statut === 'annulee') return back()->with('error','Cette facture est annulée.');
        if ($facture->estPayee) return back()->with('error','Cette facture est déjà payée.');
        FactureService::marquerPayee($facture);
        $this->oublierCaches($facture->institut_id);
        return back()->with('success','Facture marquée payée. Vente créée.');
    }

    /** Invalide les caches de chiffres d'affaires et des KPI du module. */
    private function oublierCaches(string $institutId): void
    {
        Cache::forget("facture_stats:{$institutId}");
        Cache::forget("dashboard_full:{$institutId}:" . now()->toDateString());
        Cache::forget("dashboard_basic:{$institutId}:" . now()->toDateString());
    }

    public function annuler(Facture $facture)
    {
        if ($facture->statut === 'annulee') {
            return back()->with('error', 'Cette facture est déjà annulée.');
        }

        $montantPaye = $facture->montant_paye;

        DB::beginTransaction();
        try {
            // Toutes les ventes rattachées à la facture : celles des paiements
            // partiels ET celle créée par « marquer payée » (sans paiement).
            $venteIds = $facture->paiements->pluck('vente_id')
                ->push($facture->vente_id)
                ->filter()
                ->unique()
                ->values();

            foreach ($venteIds as $venteId) {
                $vente = \App\Models\Vente::with('items')->find($venteId);
                if (! $vente) continue;

                // Remettre en stock les produits vendus par cette facture
                foreach ($vente->items as $item) {
                    if ($item->type === 'produit' && $item->item_id) {
                        \App\Models\Produit::where('id', $item->item_id)
                            ->increment('stock', (int) $item->quantite);
                    }
                }

                $vente->items()->delete();
                $vente->delete();
            }

            // Supprimer les paiements
            $facture->paiements()->delete();

            // Un avoir trace la contre-passation des sommes déjà encaissées :
            // on ne se contente pas de supprimer les écritures.
            $avoir = null;
            if ($montantPaye > 0) {
                $avoir = \App\Models\Avoir::create([
                    'institut_id' => $facture->institut_id,
                    'facture_id'  => $facture->id,
                    'client_id'   => $facture->client_id,
                    'user_id'     => Auth::id(),
                    'numero'      => FactureService::genererNumeroAvoir($facture->institut_id),
                    'montant'     => $montantPaye,
                    'motif'       => 'Annulation de la facture ' . $facture->numero,
                    'statut'      => 'emis',
                ]);
            }

            // Marquer la facture comme annulée
            $facture->update(['statut' => 'annulee', 'montant_paye' => 0, 'vente_id' => null]);

            $this->oublierCaches($facture->institut_id);

            DB::commit();

            $msg = $montantPaye > 0
                ? 'Facture annulée. Avoir ' . $avoir->numero . ' émis pour '
                  . number_format($montantPaye, 0, ',', ' ') . ' F : les ventes liées ont été retirées du chiffre d\'affaires.'
                : 'Facture annulée.';

            return back()->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur lors de l\'annulation : ' . $e->getMessage());
        }
    }
    public function destroy(Facture $facture) {
        if ($facture->montant_paye > 0) {
            return back()->with('error', 'Cette facture a reçu des paiements : annulez-la plutôt que de la supprimer.');
        }
        $facture->items()->delete();
        $facture->delete();
        $this->oublierCaches($facture->institut_id);
        return redirect()->route('dashboard.factures.index')->with('success','Facture supprimée.');
    }

    /** Envoyer la facture par email au client */
    public function envoyerEmail(Facture $facture)
    {
        $clientEmail = $facture->client_email ?: ($facture->client->email ?? null);
        if (!$clientEmail) return back()->with('error', 'Aucune adresse email pour ce client.');

        $facture->load('items', 'client', 'institut');
        $institut = $facture->institut;
        $pdf = Pdf::loadView('pdf.facture-module', ['facture' => $facture, 'institut' => $institut]);

        \Illuminate\Support\Facades\Mail::send('emails.facture', ['facture' => $facture], function ($message) use ($facture, $institut, $pdf, $clientEmail) {
            $message->to($clientEmail, $facture->client_nom_complet ?: ($facture->client->nom_complet ?? 'Client'))
                    ->subject('Facture ' . $facture->numero . ' — ' . ($institut?->nom ?? 'Maelya Gestion'))
                    ->attachData($pdf->output(), "facture-{$facture->numero}.pdf", ['mime' => 'application/pdf']);
        });

        return back()->with('success', 'Facture envoyée par email à ' . ($facture->client_nom_complet ?: 'votre client') . '.');
    }

    /** Duplique une facture : pré-remplit le formulaire de création. */
    public function dupliquer(Facture $facture)
    {
        $facture->load('items');

        session()->flash('duplicate_facture', [
            'client_id' => $facture->client_id,
            'client'    => $facture->client ? [
                'id'            => $facture->client->id,
                'prenom'        => $facture->client->prenom,
                'nom'           => $facture->client->nom,
                'nom_affichage' => $facture->client->nom_affichage,
                'telephone'     => $facture->client->telephone,
                'email'         => $facture->client->email,
                'adresse'       => $facture->client->adresse,
                'initiale'      => strtoupper(substr($facture->client->prenom ?: $facture->client->nom, 0, 1)),
            ] : null,
            'titre'                => $facture->titre,
            'notes'                => $facture->notes,
            'conditions'           => $facture->conditions,
            'tva_applicable'       => $facture->tva_applicable,
            'tva_taux'             => (float) $facture->tva_taux,
            'remise_globale_type'  => $facture->remise_globale_type,
            'remise_globale_valeur'=> $facture->remise_globale_valeur,
            'lignes'               => $facture->items->map(fn ($i) => [
                'designation'   => $i->designation,
                'quantite'      => $i->quantite,
                'prix_unitaire' => $i->prix_unitaire,
                'remise_type'   => $i->remise_type,
                'remise_valeur' => $i->remise_valeur,
                'tva_taux'      => $i->tva_taux,
                'produit_id'    => $i->produit_id,
                'prestation_id' => $i->prestation_id,
            ])->toArray(),
        ]);

        return redirect()->route('dashboard.factures.create')
            ->with('success', 'Facture ' . $facture->numero . ' dupliquée : vérifiez les dates puis enregistrez.');
    }

    /** Crée une facture à partir d'une vente existante. */
    public function creerDepuisVente(\App\Models\Vente $vente)
    {
        $vente->load('items', 'client');

        session()->flash('duplicate_facture', [
            'client_id' => $vente->client_id,
            'client'    => $vente->client ? [
                'id'            => $vente->client->id,
                'prenom'        => $vente->client->prenom,
                'nom'           => $vente->client->nom,
                'nom_affichage' => $vente->client->nom_affichage,
                'telephone'     => $vente->client->telephone,
                'email'         => $vente->client->email,
                'adresse'       => $vente->client->adresse,
                'initiale'      => strtoupper(substr($vente->client->prenom ?: $vente->client->nom, 0, 1)),
            ] : null,
            'notes'  => 'Facture établie à partir de la vente ' . $vente->numero,
            'lignes' => $vente->items->map(fn ($i) => [
                'designation'   => $i->nom_snapshot ?: 'Article',
                'quantite'      => $i->quantite,
                'prix_unitaire' => $i->prix_snapshot,
                'produit_id'    => $i->type === 'produit' ? $i->item_id : null,
                'prestation_id' => $i->type !== 'produit' ? $i->item_id : null,
            ])->toArray(),
        ]);

        return redirect()->route('dashboard.factures.create')
            ->with('success', 'Facture pré-remplie depuis la vente ' . $vente->numero . '.');
    }

    /** Envoie une relance manuelle au client pour une facture impayée. */
    public function relancer(Facture $facture)
    {
        if ($facture->estPayee) {
            return back()->with('error', 'Cette facture est déjà réglée.');
        }
        if ($facture->statut === 'annulee') {
            return back()->with('error', 'Cette facture est annulée.');
        }

        $email = $facture->client_email ?: ($facture->client->email ?? null);
        if (! $email) {
            return back()->with('error', 'Aucune adresse email pour ce client.');
        }

        $facture->load('items', 'client', 'institut');

        Mail::to($email)->send(new FactureARelancer($facture));

        FactureRelance::firstOrCreate(
            ['facture_id' => $facture->id, 'palier' => 'manuel', 'canal' => 'email'],
            ['institut_id' => $facture->institut_id, 'envoye_le' => now()]
        );

        return back()->with('success', 'Relance envoyée à ' . $email . '.');
    }

    public function updatePiedPage(Request $request, Facture $facture)
    {
        $data = $request->validate([
            'pied_page_afficher' => 'boolean',
            'pied_page_texte'    => 'nullable|string|max:1000',
        ]);
        $facture->update([
            'pied_page_afficher' => $data['pied_page_afficher'] ?? true,
            'pied_page_texte'    => $data['pied_page_texte'] ?? null,
        ]);
        return back()->with('success', 'Pied de page mis à jour.');
    }
}
