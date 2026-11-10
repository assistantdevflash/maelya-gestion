<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\CategoriePrestation;
use App\Models\CategorieProduit;
use App\Models\Client;
use App\Models\CodeReduction;
use App\Models\MouvementStock;
use App\Models\HistoriquePoints;
use App\Models\ProgrammeFidelite;
use App\Models\Prestation;
use App\Models\Produit;
use App\Models\Vente;
use App\Services\FactureNumeroService;
use App\Services\StockService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
        return view('dashboard.caisse.index', ['simple' => false]);
    }

    /**
     * Caisse simplifiée : pour les commerces / supermarchés.
     * Produits uniquement, ajout au panier par scan ou recherche, sans grille
     * ni catégories et sans vente à crédit.
     *
     * Réservée à Premium+ (scan code-barres), mais offerte pendant l'essai.
     */
    public function caisseSimplifiee()
    {
        if (! auth()->user()->peutUtiliserCaisseSimplifiee()) {
            return redirect()->route('abonnement.upgrade', ['feature' => 'scan_code_barre']);
        }

        return view('dashboard.caisse.index', ['simple' => true]);
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
            'pourboire' => ['nullable', 'integer', 'min:0', 'max:10000000'],
        ]);

        $items = json_decode($request->panier_json, true);

        if (!is_array($items) || empty($items)) {
            return back()->withErrors(['panier_json' => 'Le panier est vide.']);
        }

        try {
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
                        $itemId = $item['id'];
                    } elseif ($item['type'] === 'libre') {
                        // Article hors catalogue — prix et nom fournis directement par l'utilisateur
                        $nom = strip_tags(substr((string) ($item['nom'] ?? ''), 0, 150));
                        $prix = max(1, min(10_000_000, (int) ($item['prix'] ?? 0)));
                        abort_if(!$nom || $prix <= 0, 422, 'Article libre invalide.');
                        $itemId = null;
                        $quantite = (int) ($item['quantite'] ?? 1);

                        // Type et catégorie optionnels pour les stats
                        $typeLibre = in_array($item['typeLibre'] ?? null, ['prestation', 'produit']) ? $item['typeLibre'] : null;
                        $categorieId = null;
                        if ($typeLibre && !empty($item['categorieId'] ?? null)) {
                            $catId = (string) $item['categorieId'];
                            if ($typeLibre === 'prestation') {
                                $catExists = CategoriePrestation::where('institut_id', $this->institutId())
                                    ->where('id', $catId)->exists();
                            } else {
                                $catExists = CategorieProduit::where('institut_id', $this->institutId())
                                    ->where('id', $catId)->exists();
                            }
                            if ($catExists) {
                                $categorieId = $catId;
                            }
                        }

                        $sousTotal = $prix * $quantite;
                        $totalBrut += $sousTotal;

                        $itemsToSave[] = [
                            'type' => $item['type'],
                            'item_id' => $itemId,
                            'type_libre' => $typeLibre,
                            'categorie_id' => $categorieId,
                            'nom_snapshot' => $nom,
                            'prix_snapshot' => $prix,
                            'quantite' => $quantite,
                            'sous_total' => $sousTotal,
                        ];
                        continue;
                    } else {
                        $model = Produit::findOrFail($item['id']);
                        $prix = $model->prix_vente;
                        $nom = $model->nom;
                        $itemId = $item['id'];
                    }

                    $quantite = (int) ($item['quantite'] ?? 1);
                    $sousTotal = $prix * $quantite;
                    $totalBrut += $sousTotal;

                    $itemsToSave[] = [
                        'type' => $item['type'],
                        'item_id' => $itemId,
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

                        // Si ce code est lié à un avoir, marquer l'avoir comme utilisé
                        $avoirLie = \App\Models\Avoir::where('code_reduction_id', $code->id)
                            ->where('statut', 'emis')
                            ->first();
                        if ($avoirLie) {
                            $avoirLie->update(['statut' => 'utilise']);
                        }
                    }
                }

                $total = max(0, $totalBrut - $remise);

                $vente = Vente::create([
                    'institut_id' => $this->institutId(),
                    'client_id' => $request->client_id ?: null,
                    'user_id' => Auth::id(),
                    'total' => $total,
                    'remise' => $remise,
                    'pourboire' => max(0, (int) $request->pourboire),
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

                // Numérotation facture FAC-{année}-{seq} (atomique dans la transaction)
                $vente->numero_facture = app(FactureNumeroService::class)->generate($this->institutId());
                $vente->save();

                foreach ($itemsToSave as $item) {
                    $item['id'] = (string) Str::uuid();
                    $vente->items()->create($item);

                    // Sortie de stock centralisée : contrôle de disponibilité et
                    // traçabilité garantis par StockService.
                    if ($item['type'] === 'produit') {
                        $produit = Produit::find($item['item_id']);
                        if ($produit) {
                            app(StockService::class)->sortir($produit, (int) $item['quantite'], [
                                'type'     => StockService::SORTIE_VENTE,
                                'vente_id' => $vente->id,
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
        } catch (\App\Services\StockInsuffisantException $e) {
            // Rupture détectée pendant la vente : on revient en caisse avec un
            // message clair plutôt qu'une erreur 500. Le panier est conservé
            // par le navigateur (localStorage) et rechargé automatiquement.
            return back()->with('error', $e->getMessage())->withInput();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[Vente] ' . $e->getMessage());

            return back()
                ->with('error', "La vente n'a pas pu être enregistrée : " . $e->getMessage())
                ->withInput();
        }

        // ── Invalider les caches ──
        Cache::forget("dashboard_full:{$this->institutId()}:" . now()->toDateString());
        Cache::forget("dashboard_basic:{$this->institutId()}:" . now()->toDateString());

        // Le catalogue de la caisse est mémorisé une heure : sans cette purge,
        // le stock affiché restait figé après la vente.
        \App\Livewire\Caisse::purgerCache($this->institutId());

        if ($request->imprimer) {
            // Le PDF est généré ici, pendant que le caissier voit déjà le
            // voile « Encaissement en cours… ». La fenêtre d'impression
            // s'ouvre ensuite sur un fichier prêt, sans attendre dompdf.
            try {
                app(\App\Services\TicketPdfService::class)->generer($vente);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[Ticket] Pré-génération impossible : ' . $e->getMessage());
            }

            // Rediriger vers la caisse avec l'ID de la vente pour déclencher l'impression
            return redirect()->route('dashboard.caisse', ['print' => $vente->id])
                ->with('success', 'Vente #' . substr($vente->id, 0, 8) . ' enregistrée — ' . number_format($vente->total, 0, ',', ' ') . ' F');
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
                ->selectRaw("user_id, COUNT(*) as nb_ventes, SUM(CASE WHEN statut != 'annulee' THEN CASE WHEN mode_paiement = 'credit' THEN montant_paye ELSE total END ELSE 0 END) as total_ventes")
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
        // Les employés peuvent voir les détails de toutes les ventes
        $vente->load('items', 'client', 'user', 'codeReduction', 'avoirs.codeReduction', 'avoirs.user');
        return view('dashboard.caisse.show', compact('vente'));
    }

    /**
     * Détail de la dernière vente encaissée, pour la fenêtre d'annulation
     * rapide de la caisse.
     *
     * On renvoie de quoi identifier la vente sans ambiguïté : numéro, heure,
     * client, caissier, lignes et total. Le caissier doit reconnaître sa vente
     * avant de confirmer.
     */
    public function derniereJson()
    {
        if (! Auth::user()->isAdmin()) {
            return response()->json(['vente' => null, 'message' => 'Accès réservé aux administrateurs.'], 403);
        }

        $vente = $this->derniereVenteAnnulable();

        if (! $vente) {
            return response()->json([
                'vente'   => null,
                'message' => "Aucune vente encaissée dans les dernières 24 heures.",
            ]);
        }

        $vente->loadMissing('items', 'client', 'user');

        return response()->json([
            'vente' => [
                'id'                => $vente->id,
                'numero'            => $vente->numero,
                'date'              => $vente->created_at->copy()->locale('fr')->isoFormat('D MMMM YYYY [à] HH:mm'),
                'depuis'            => $vente->created_at->diffForHumans(),
                'total'             => (int) $vente->total,
                'remise'            => (int) $vente->remise,
                'pourboire'         => (int) $vente->pourboire,
                'mode_paiement'     => $vente->mode_paiement,
                'mode_paiement_lib' => $this->libelleModePaiement($vente->mode_paiement),
                'client'            => $vente->client?->nom_affichage,
                'caissier'          => $vente->user?->name,
                'nb_articles'       => (int) $vente->items->sum('quantite'),
                'items'             => $vente->items->map(fn ($item) => [
                    'nom'        => $item->nom_snapshot,
                    'quantite'   => (int) $item->quantite,
                    'prix'       => (int) $item->prix_snapshot,
                    'sous_total' => (int) $item->sous_total,
                ])->values(),
            ],
        ]);
    }

    private function libelleModePaiement(?string $mode): string
    {
        return match ($mode) {
            'cash'         => 'Espèces',
            'carte'        => 'Carte bancaire',
            'mobile_money' => 'Mobile money',
            'mixte'        => 'Paiement mixte',
            'credit'       => 'Crédit',
            default        => ucfirst((string) $mode),
        };
    }

    /**
     * Dernière vente annulable de l'établissement (24 dernières heures).
     */
    private function derniereVenteAnnulable(): ?Vente
    {
        return Vente::withoutGlobalScopes()
            ->where('institut_id', $this->institutId())
            ->where('statut', 'validee')
            ->where('created_at', '>=', now()->subDay())
            ->latest('created_at')
            ->first();
    }

    /**
     * Annulation en un clic de la dernière vente encaissée.
     *
     * Une erreur de caisse (mauvais client, mauvais montant) doit pouvoir se
     * corriger sans chercher la vente dans la liste : on reprend la dernière
     * vente validée des dernières 24 heures et on applique exactement la même
     * procédure que l'annulation classique (stock rendu, points reversés).
     */
    public function annulerDerniere(Request $request)
    {
        if (! Auth::user()->isAdmin()) {
            abort(403, 'Seul un administrateur peut annuler une vente.');
        }

        $vente = $this->derniereVenteAnnulable();

        if (! $vente) {
            return back()->with('error', "Aucune vente encaissée dans les dernières 24 heures.");
        }

        $request->merge([
            'motif_annulation' => $request->input('motif_annulation')
                ?: 'Annulation rapide depuis la caisse',
        ]);

        return $this->annuler($request, $vente);
    }

    public function annuler(Request $request, Vente $vente)
    {
        // Seul l'admin peut annuler une vente
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Seul un administrateur peut annuler une vente.');
        }

        if ($vente->statut === 'annulee') {
            return back()->with('error', 'Cette vente est déjà annulée.');
        }

        $data = $request->validate([
            'motif_annulation' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($vente, $data) {
            // Réintégration du stock pour chaque produit vendu
            foreach ($vente->items as $item) {
                if ($item->type === 'produit' && $item->item_id) {
                    $produit = Produit::withoutGlobalScopes()->find($item->item_id);
                    if ($produit) {
                        app(StockService::class)->entrer($produit, (int) $item->quantite, [
                            'type'     => StockService::ANNULATION_VENTE,
                            'vente_id' => $vente->id,
                            'note'     => 'Annulation de vente',
                        ]);
                    }
                }
            }

            // Reversement des points de fidélité gagnés lors de la vente
            $pointsGagnes = HistoriquePoints::where('vente_id', $vente->id)
                ->where('type', 'gain')
                ->sum('points');

            if ($pointsGagnes > 0 && $vente->client_id) {
                $client = $vente->client()->withoutGlobalScopes()->first();
                if ($client) {
                    $pointsARetirer = min($pointsGagnes, (int) $client->points_fidelite);
                    if ($pointsARetirer > 0) {
                        $client->decrement('points_fidelite', $pointsARetirer);
                        HistoriquePoints::create([
                            'institut_id' => $this->institutId(),
                            'client_id'   => $client->id,
                            'vente_id'    => $vente->id,
                            'points'      => $pointsARetirer,
                            'type'        => 'perte',
                            'description' => "Annulation vente #{$vente->numero}",
                        ]);
                    }
                }
            }

            $vente->update([
                'statut'           => 'annulee',
                'motif_annulation' => $data['motif_annulation'],
                'annulee_le'       => now(),
                'annulee_par'      => Auth::id(),
            ]);
        });

        // Le stock vient de remonter : le catalogue en cache doit être rafraîchi.
        \App\Livewire\Caisse::purgerCache($this->institutId());
        Cache::forget("dashboard_full:{$this->institutId()}:" . now()->toDateString());
        Cache::forget("dashboard_basic:{$this->institutId()}:" . now()->toDateString());

        // Le ticket en cache d'une vente annulée ne doit plus être réimprimé.
        app(\App\Services\TicketPdfService::class)->oublier($vente);

        return back()->with('success', 'Vente annulée : stock restauré et points reversés.');
    }

    public function ticketPdf(Vente $vente)
    {
        if (Auth::user()->isEmploye() && $vente->user_id !== Auth::id()) {
            abort(403);
        }

        // Ticket mis en cache sur disque : la deuxième ouverture — et celle
        // qui suit immédiatement l'encaissement — est quasi instantanée.
        return app(\App\Services\TicketPdfService::class)->reponse($vente);
    }

    /**
     * Ticket PDF public (pas d'auth requise) — accès sécurisé par UUID de la vente.
     */
    public function ticketPdfPublic(string $id)
    {
        $vente = Vente::where('id', $id)->where('statut', 'validee')->firstOrFail();

        return app(\App\Services\TicketPdfService::class)->reponse($vente);
    }

    public function facturePdf(Vente $vente)
    {
        if (Auth::user()->isEmploye() && $vente->user_id !== Auth::id()) {
            abort(403);
        }

        if (empty($vente->numero_facture)) {
            DB::transaction(function () use ($vente) {
                $vente->numero_facture = app(FactureNumeroService::class)->generate($vente->institut_id);
                $vente->save();
            });
        }

        $vente->load('items', 'client', 'institut', 'user');
        $pdf = Pdf::loadView('pdf.facture', compact('vente'))->setPaper('a4', 'portrait');
        return $pdf->stream("facture-{$vente->numero_facture}.pdf");
    }

    /**
     * Génère un lien de sondage pour une vente
     */
    public function genererSondage(Vente $vente)
    {
        // Vérifications
        if (Auth::user()->isEmploye() && $vente->user_id !== Auth::id()) {
            abort(403);
        }

        if ($vente->statut !== 'validee') {
            return back()->with('error', 'Le sondage ne peut être généré que pour une vente validée.');
        }

        if (!$vente->client) {
            return back()->with('error', 'Cette vente n\'a pas de client associé.');
        }

        // Vérifier si un avis existe déjà pour cette vente
        $avis = \App\Models\AvisClient::withoutGlobalScopes()
            ->where('vente_id', $vente->id)
            ->first();

        if ($avis) {
            return back()->with('info', 'Un lien de sondage existe déjà pour cette vente.');
        }

        // Créer l'avis client
        $avis = \App\Models\AvisClient::create([
            'institut_id'     => $vente->institut_id,
            'client_id'       => $vente->client_id,
            'vente_id'        => $vente->id,
            'client_nom_snap' => $vente->client->nom . ' ' . ($vente->client->prenom ?? ''),
            'statut'          => 'en_attente',
        ]);

        return back()->with('success', 'Lien de sondage généré avec succès ! Vous pouvez maintenant l\'envoyer par email ou WhatsApp.');
    }

    /**
     * Envoie le sondage par email
     */
    public function envoyerSondageEmail(Vente $vente)
    {
        // Vérifications
        if (Auth::user()->isEmploye() && $vente->user_id !== Auth::id()) {
            abort(403);
        }

        if ($vente->statut !== 'validee') {
            return back()->with('error', 'Le sondage ne peut être envoyé que pour une vente validée.');
        }

        if (!$vente->client || !$vente->client->email) {
            return back()->with('error', 'Le client n\'a pas d\'adresse email.');
        }

        // Récupérer ou créer l'avis
        $avis = \App\Models\AvisClient::withoutGlobalScopes()
            ->where('vente_id', $vente->id)
            ->first();

        if (!$avis) {
            $avis = \App\Models\AvisClient::create([
                'institut_id'     => $vente->institut_id,
                'client_id'       => $vente->client_id,
                'vente_id'        => $vente->id,
                'client_nom_snap' => $vente->client->nom . ' ' . ($vente->client->prenom ?? ''),
                'statut'          => 'en_attente',
            ]);
        }

        if ($avis->repondu_le) {
            return back()->with('info', 'Le client a déjà répondu au sondage.');
        }

        // Envoyer l'email
        try {
            \Illuminate\Support\Facades\Mail::to($vente->client->email)
                ->send(new \App\Mail\AvisDemande($avis, null, $vente));
            
            return back()->with('success', 'Sondage envoyé par email à ' . $vente->client->email . ' !');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[Sondage Email] ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
        }
    }
}
