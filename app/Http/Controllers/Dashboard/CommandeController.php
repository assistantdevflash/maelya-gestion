<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Mail\CommandeStatutUpdatedClient;
use App\Models\Commande;
use App\Models\Vente;
use App\Models\VenteItem;
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
}
