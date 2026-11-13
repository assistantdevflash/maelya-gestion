<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Mail\CommandeStatutUpdatedClient;
use App\Models\Commande;
use App\Models\Vente;
use App\Models\VenteItem;
use App\Services\CommandeService;
use App\Services\StockInsuffisantException;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CommandeController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private CommandeService $service) {}
    /**
     * Liste des commandes
     */
    public function index(Request $request)
    {
        $institutId = session('current_institut_id', auth()->user()->institut_id);
        $institut = \App\Models\Institut::find($institutId);
        $zones = is_array($institut?->boutique_zones_livraison) ? $institut->boutique_zones_livraison : [];

        $query = Commande::where('institut_id', $institutId)
            ->with(['client', 'items.produit'])
            ->orderBy('created_at', 'desc');

        // Filtres
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('payee')) {
            $query->where('payee', $request->payee === '1');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('numero', 'like', "%{$search}%")
                    ->orWhere('client_nom', 'like', "%{$search}%")
                    ->orWhere('client_prenom', 'like', "%{$search}%")
                    ->orWhere('client_telephone', 'like', "%{$search}%")
                    ->orWhere('client_email', 'like', "%{$search}%");
            });
        }

        // Période : date exacte ou plage
        if ($request->filled('du')) {
            $query->whereDate('created_at', '>=', $request->du);
        }
        if ($request->filled('au')) {
            $query->whereDate('created_at', '<=', $request->au);
        }

        // Zone de livraison : le libellé est retrouvé par SearchController via
        // les frais, on filtre donc sur le montant de livraison.
        if ($request->filled('zone')) {
            $zones = is_array($institut?->boutique_zones_livraison) ? $institut->boutique_zones_livraison : [];
            $index = (int) $request->zone;
            if (isset($zones[$index]['frais'])) {
                $query->where('frais_livraison', (int) $zones[$index]['frais']);
            }
        }

        $commandes = $query->paginate(20)->withQueryString();

        // Statistiques (1 seule requête au lieu de 4)
        $stats = Cache::remember("cmd_stats:{$institutId}", now()->addMinutes(3), function () use ($institutId) {
            $row = Commande::where('institut_id', $institutId)
                ->selectRaw("SUM(CASE WHEN statut='nouvelle' THEN 1 ELSE 0 END) as nouvelles, SUM(CASE WHEN statut IN('acceptee','en_preparation','en_livraison') THEN 1 ELSE 0 END) as en_cours, SUM(CASE WHEN statut='livree' THEN 1 ELSE 0 END) as livrees, COALESCE(SUM(CASE WHEN payee=1 THEN total ELSE 0 END),0) as total_ca")
                ->first();
            return ['nouvelles' => (int) $row->nouvelles, 'en_cours' => (int) $row->en_cours, 'livrees' => (int) $row->livrees, 'total_ca' => (int) $row->total_ca];
        });

        return view('dashboard.boutique.commandes.index', compact('commandes', 'stats', 'zones'));
    }

    /**
     * Invalide les compteurs mis en cache (liste + tableau de bord).
     * Sans cela, les indicateurs restaient faux pendant plusieurs minutes.
     */
    private function oublierCaches(?string $institutId = null): void
    {
        $institutId = $institutId ?: session('current_institut_id', auth()->user()?->institut_id);

        if (! $institutId) {
            return;
        }

        Cache::forget("cmd_stats:{$institutId}");
        DashboardController::purgerCache($institutId);
    }

    /**
     * Afficher les détails d'une commande
     */
    public function show(Commande $commande)
    {
        $this->authorize('view', $commande);

        $commande->load(['client', 'items.produit.imagePrincipale', 'vente']);

        return view('dashboard.boutique.commandes.show', compact('commande'));
    }

    /**
     * Mettre à jour le statut d'une commande
     */
    public function updateStatut(Request $request, Commande $commande)
    {
        $this->authorize('update', $commande);

        $data = $request->validate([
            'statut' => 'required|in:nouvelle,acceptee,en_preparation,en_livraison,livree,annulee,refusee',
        ]);

        $ancienStatut = $commande->statut;
        $nouveauStatut = $data['statut'];

        // Validation des transitions de statut
        $transitionsValides = [
            'nouvelle' => ['acceptee', 'refusee', 'annulee'],
            'acceptee' => ['en_preparation', 'annulee'],
            'en_preparation' => ['en_livraison', 'annulee'],
            'en_livraison' => ['livree'],
            'livree' => [],
            'annulee' => [],
            'refusee' => [],
        ];

        if (!in_array($nouveauStatut, $transitionsValides[$ancienStatut] ?? [])) {
            return back()->with('error', 'Cette transition de statut n\'est pas autorisée.');
        }

        try {
            if ($nouveauStatut === 'acceptee') {
                // Le stock est réservé dès l'acceptation : plus de survente
                // entre l'acceptation et la livraison.
                $this->service->reserverStock($commande);
                $commande->changerStatut($nouveauStatut);
            } elseif (in_array($nouveauStatut, ['annulee', 'refusee'], true)) {
                $this->service->annuler($commande, $nouveauStatut, null, 'admin');
            } else {
                $commande->changerStatut($nouveauStatut);
            }
        } catch (StockInsuffisantException $e) {
            return back()->with('error', $e->getMessage());
        }

        // Envoyer un email au client
        try {
            if ($commande->client_email) {
                Mail::to($commande->client_email)
                    ->send(new CommandeStatutUpdatedClient($commande));
            }
        } catch (\Exception $e) {
            \Log::error('Erreur envoi email statut commande: ' . $e->getMessage());
        }

        $this->oublierCaches($commande->institut_id);

        return back()->with('success', 'Statut de la commande mis à jour avec succès.');
    }

    /**
     * Marquer une commande comme payée et créer la vente automatiquement
     */
    public function marquerPayee(Commande $commande)
    {
        $this->authorize('update', $commande);

        if (!$commande->peutEtreMarqueePayee()) {
            return back()->with('error', 'Cette commande ne peut pas être marquée comme payée.');
        }

        if (! $commande->stockReserve()) {
            // Commande acceptée avant la réservation du stock : on vérifie la
            // disponibilité avant de facturer.
            foreach ($commande->items as $item) {
                if ($item->produit && $item->produit->stock < $item->quantite) {
                    return back()->with('error', "Stock insuffisant pour {$item->produit->nom} : la commande ne peut pas être encaissée.");
                }
            }
        }

        try {
            $this->service->encaisser($commande);

            $this->oublierCaches($commande->institut_id);

            // Envoyer l'événement Purchase au Pixel Facebook
            \App\Jobs\SendFacebookPurchaseEvent::dispatch($commande);

            return back()->with('success', 'Commande marquée comme payée et vente créée avec succès.');

        } catch (\Throwable $e) {
            return back()->with('error', 'Erreur lors de la création de la vente : ' . $e->getMessage());
        }
    }

    /**
     * Mettre à jour les notes admin d'une commande
     */
    public function updateNotes(Request $request, Commande $commande)
    {
        $this->authorize('update', $commande);

        $data = $request->validate([
            'notes_admin' => 'nullable|string|max:5000',
        ]);

        $commande->update($data);

        return back()->with('success', 'Notes mises à jour avec succès.');
    }

    /**
     * Mettre à jour une commande avant acceptation (modifier quantités, zone, retirer articles)
     */
    public function update(Request $request, Commande $commande)
    {
        $this->authorize('update', $commande);

        if ($commande->statut !== 'nouvelle') {
            return back()->with('error', 'Seules les commandes en statut "nouvelle" peuvent être modifiées.');
        }

        $data = $request->validate([
            'frais_livraison' => 'nullable|integer|min:0',
            'zone_index' => 'nullable|integer|min:0',
            'items' => 'required|array',
            'items.*.id' => 'required|string',
            'items.*.quantite' => 'required|integer|min:0|max:999',
            'items.*.supprimer' => 'nullable',
            'date_livraison_prevue' => 'nullable|date',
            'creneau_livraison' => 'nullable|string|max:40',
        ]);

        try {
            DB::beginTransaction();

            $sousTotal = 0;

            foreach ($data['items'] as $itemData) {
                $cmdItem = $commande->items()->find($itemData['id']);
                if (!$cmdItem) continue;

                if (($itemData['supprimer'] ?? null) == '1' || (int)$itemData['quantite'] === 0) {
                    $cmdItem->delete();
                    continue;
                }

                $qte = (int) $itemData['quantite'];
                $cmdItem->update([
                    'quantite' => $qte,
                    'sous_total' => $cmdItem->prix_snapshot * $qte,
                ]);
            }

            // Le sous-total est recalculé sur *toutes* les lignes restantes :
            // une ligne absente du formulaire (page périmée) ne doit pas
            // disparaître du montant.
            $sousTotal = (int) $commande->items()->sum('sous_total');

            // Calculer les frais selon la zone
            $zones = is_array($commande->institut->boutique_zones_livraison) ? $commande->institut->boutique_zones_livraison : [];
            $fraisLivraison = $data['frais_livraison'] ?? $commande->frais_livraison;
            if (isset($data['zone_index']) && isset($zones[$data['zone_index']])) {
                $fraisLivraison = (int)($zones[$data['zone_index']]['frais'] ?? $fraisLivraison);
            }

            $total = $sousTotal + $fraisLivraison;

            // Vérifier qu'il reste au moins un article
            if ($commande->items()->count() === 0) {
                DB::rollBack();
                return back()->withInput()->withErrors(['items' => 'Vous ne pouvez pas retirer tous les articles.']);
            }

            $commande->update([
                'sous_total' => $sousTotal,
                'frais_livraison' => $fraisLivraison,
                'total' => $total,
                'date_livraison_prevue' => $data['date_livraison_prevue'] ?? $commande->date_livraison_prevue,
                'creneau_livraison' => $data['creneau_livraison'] ?? $commande->creneau_livraison,
            ]);

            DB::commit();

            $this->oublierCaches($commande->institut_id);

            return back()->with('success', 'Commande mise à jour avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur lors de la mise à jour : ' . $e->getMessage());
        }
    }

    /**
     * Supprimer une commande (uniquement si annulée ou refusée)
     */
    public function destroy(Commande $commande)
    {
        $this->authorize('delete', $commande);

        if (!in_array($commande->statut, ['annulee', 'refusee'])) {
            return back()->with('error', 'Seules les commandes annulées ou refusées peuvent être supprimées.');
        }

        $numero = $commande->numero;
        $institutId = $commande->institut_id;
        $commande->delete();

        $this->oublierCaches($institutId);

        return redirect()->route('dashboard.boutique.commandes.index')
            ->with('success', "Commande {$numero} supprimée avec succès.");
    }

    /**
     * Actions groupées depuis la liste : accepter ou refuser plusieurs
     * commandes en une fois (le cas d'usage du matin).
     */
    public function actionsGroupees(Request $request)
    {
        $data = $request->validate([
            'ids'    => 'required|array|min:1',
            'ids.*'  => 'string',
            'action' => 'required|in:accepter,refuser',
        ]);

        $institutId = session('current_institut_id', auth()->user()->institut_id);

        $commandes = Commande::where('institut_id', $institutId)
            ->whereIn('id', $data['ids'])
            ->get();

        $traitees = 0;
        $erreurs  = [];

        foreach ($commandes as $commande) {
            $this->authorize('update', $commande);

            if ($commande->statut !== 'nouvelle') {
                continue;
            }

            try {
                if ($data['action'] === 'accepter') {
                    $this->service->reserverStock($commande);
                    $commande->changerStatut('acceptee');
                } else {
                    $this->service->annuler($commande, 'refusee', null, 'admin');
                }

                $traitees++;
            } catch (StockInsuffisantException $e) {
                $erreurs[] = $commande->numero . ' : ' . $e->getMessage();
            }
        }

        $this->oublierCaches($institutId);

        if ($erreurs !== []) {
            return back()->with('error', $traitees . ' commande(s) traitée(s). '
                . \App\Helpers\TexteHelper::pluriel(count($erreurs), 'échec', 'échecs')
                . ' : ' . implode(' · ', $erreurs));
        }

        return back()->with('success', $traitees . ' '
            . \App\Helpers\TexteHelper::pluriel($traitees, 'commande traitée', 'commandes traitées') . '.');
    }

    /** Bon de livraison imprimable. */
    public function bonLivraison(Commande $commande)
    {
        $this->authorize('view', $commande);

        $commande->load(['items', 'client', 'institut']);

        $pdf = Pdf::loadView('pdf.bon-livraison', compact('commande'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream("bon-livraison-{$commande->numero}.pdf");
    }

    /**
     * Compter les nouvelles commandes (pour polling temps réel)
     */
    public function countNouvelles()
    {
        $institutId = session('current_institut_id', auth()->user()->institut_id);
        $count = Commande::where('institut_id', $institutId)->nouvelles()->count();
        return response()->json(['count' => $count]);
    }

    /**
     * Exporter les commandes en CSV
     */
    public function export(Request $request)
    {
        $institutId = session('current_institut_id', auth()->user()->institut_id);

        $query = Commande::where('institut_id', $institutId)
            ->with('items')
            ->orderBy('created_at', 'desc');

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }
        if ($request->filled('payee')) {
            $query->where('payee', $request->payee === '1');
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('numero', 'like', "%{$search}%")
                    ->orWhere('client_nom', 'like', "%{$search}%")
                    ->orWhere('client_prenom', 'like', "%{$search}%")
                    ->orWhere('client_telephone', 'like', "%{$search}%");
            });
        }

        $commandes = $query->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="commandes-' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($commandes) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8
            fputcsv($file, ['N\u00b0 Commande', 'Date', 'Client', 'Téléphone', 'Email', 'Adresse', 'Produits', 'Sous-total', 'Livraison', 'Total', 'Statut', 'Payée', 'Mode paiement', 'Notes client'], ';');

            foreach ($commandes as $cmd) {
                $produits = $cmd->items->map(fn($i) => $i->quantite . 'x ' . $i->nom_snapshot)->implode(' | ');
                fputcsv($file, [
                    $cmd->numero,
                    $cmd->created_at->format('d/m/Y H:i'),
                    $cmd->client_prenom . ' ' . $cmd->client_nom,
                    $cmd->client_telephone,
                    $cmd->client_email ?? '',
                    $cmd->client_adresse ?? '',
                    $produits,
                    $cmd->sous_total,
                    $cmd->frais_livraison,
                    $cmd->total,
                    $cmd->statut,
                    $cmd->payee ? 'Oui' : 'Non',
                    $cmd->mode_paiement ?? 'cash',
                    $cmd->notes_client ?? '',
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Générer la facture PDF d'une commande
     */
    public function facturePdf(Commande $commande)
    {
        $this->authorize('view', $commande);

        $commande->load(['items', 'client', 'institut']);

        $pdf = Pdf::loadView('pdf.facture-commande', compact('commande'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream("facture-{$commande->numero}.pdf");
    }
}
