<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Mail\CommandeStatutUpdatedClient;
use App\Models\Commande;
use App\Models\Vente;
use App\Models\VenteItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CommandeController extends Controller
{
    use AuthorizesRequests;
    /**
     * Liste des commandes
     */
    public function index(Request $request)
    {
        $institutId = session('current_institut_id', auth()->user()->institut_id);

        $query = Commande::where('institut_id', $institutId)
            ->with(['client', 'items'])
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
                    ->orWhere('client_telephone', 'like', "%{$search}%");
            });
        }

        $commandes = $query->paginate(20);

        // Statistiques
        $stats = [
            'nouvelles' => Commande::where('institut_id', $institutId)->nouvelles()->count(),
            'en_cours' => Commande::where('institut_id', $institutId)->enCours()->count(),
            'livrees' => Commande::where('institut_id', $institutId)->livrees()->count(),
            'total_ca' => Commande::where('institut_id', $institutId)->payees()->sum('total'),
        ];

        return view('dashboard.boutique.commandes.index', compact('commandes', 'stats'));
    }

    /**
     * Afficher les détails d'une commande
     */
    public function show(Commande $commande)
    {
        $this->authorize('view', $commande);

        $commande->load(['client', 'items.produit', 'vente']);

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

        $commande->changerStatut($nouveauStatut);

        // Envoyer un email au client
        try {
            if ($commande->client_email) {
                Mail::to($commande->client_email)
                    ->send(new CommandeStatutUpdatedClient($commande));
            }
        } catch (\Exception $e) {
            \Log::error('Erreur envoi email statut commande: ' . $e->getMessage());
        }

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

        try {
            DB::beginTransaction();

            // Créer la vente
            $vente = Vente::create([
                'institut_id' => $commande->institut_id,
                'client_id' => $commande->client_id,
                'user_id' => Auth::id(),
                'total' => $commande->total,
                'montant_paye' => $commande->total,
                'mode_paiement' => 'cash',
                'statut' => 'validee',
                'remise' => 0,
                'pourboire' => 0,
            ]);

            // Créer les items de la vente
            foreach ($commande->items as $item) {
                VenteItem::create([
                    'vente_id' => $vente->id,
                    'type' => 'produit',
                    'item_id' => $item->produit_id,
                    'nom_snapshot' => $item->nom_snapshot,
                    'prix_snapshot' => $item->prix_snapshot,
                    'quantite' => $item->quantite,
                    'sous_total' => $item->sous_total,
                ]);

                // Déduire le stock
                if ($item->produit) {
                    $item->produit->decrement('stock', $item->quantite);
                }
            }

            // Mettre à jour la commande
            $commande->update([
                'payee' => true,
                'payee_at' => now(),
                'vente_id' => $vente->id,
            ]);

            DB::commit();

            return back()->with('success', 'Commande marquée comme payée et vente créée avec succès.');

        } catch (\Exception $e) {
            DB::rollBack();
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
        \Log::info('CommandeController@update appelé', ['commande_id' => $commande->id]);

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
        ]);

        \Log::info('Validation OK, items:', $data['items'] ?? []);

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
                $subTotal = $cmdItem->prix_snapshot * $qte;
                $cmdItem->update([
                    'quantite' => $qte,
                    'sous_total' => $subTotal,
                ]);
                $sousTotal += $subTotal;
            }

            // Calculer les frais selon la zone
            $zones = is_array($commande->institut->boutique_zones_livraison) ? $commande->institut->boutique_zones_livraison : [];
            $fraisLivraison = $data['frais_livraison'] ?? $commande->frais_livraison;
            if (isset($data['zone_index']) && isset($zones[$data['zone_index']])) {
                $fraisLivraison = (int)($zones[$data['zone_index']]['frais'] ?? $fraisLivraison);
            }

            $total = $sousTotal + $fraisLivraison;

            $commande->update([
                'sous_total' => $sousTotal,
                'frais_livraison' => $fraisLivraison,
                'total' => $total,
            ]);

            DB::commit();

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
        $commande->delete();

        return redirect()->route('dashboard.boutique.commandes.index')
            ->with('success', "Commande {$numero} supprimée avec succès.");
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
