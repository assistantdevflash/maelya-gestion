<?php

namespace App\Http\Controllers;

use App\Mail\NouvelleCommandeClient;
use App\Mail\NouvelleCommandeEtablissement;
use App\Models\Client;
use App\Models\Commande;
use App\Models\CommandeItem;
use App\Models\Institut;
use App\Models\Produit;
use App\Services\NotificationService;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class BoutiqueController extends Controller
{
    /**
     * Page principale de la boutique d'un établissement
     */
    public function index(string $slug)
    {
        $institut = Institut::where('slug', $slug)->firstOrFail();

        // Vérifier que la boutique est active
        if (!$institut->boutique_active) {
            abort(404, 'Cette boutique n\'est pas disponible.');
        }

        // Vérifier que le propriétaire a l'option boutique (ou est en essai)
        if (!$institut->proprietaire?->hasBoutiqueAccess()) {
            abort(404, 'Cette boutique n\'est pas disponible.');
        }

        // Récupérer les produits actifs et visibles en boutique
        $produits = Cache::remember("boutique_{$institut->id}_produits", 3600, function () use ($institut) {
            return $institut->produits()
                ->with(['categorie', 'imagePrincipale'])
                ->where('actif', true)
                ->where('visible_boutique', true)
                ->where('stock', '>', 0)
                ->orderByDesc('featured')
                ->orderBy('nom')
                ->get();
        });

        // Récupérer les catégories avec au moins un produit
        $categories = $produits->pluck('categorie')->unique()->filter();

        // Préparer les données produits pour JavaScript
        $produitsJson = $produits->map(function($p) {
            return [
                'id' => $p->id,
                'nom' => $p->nom,
                'prix' => $p->prix_vente,
                'prix_promo' => $p->prix_promo,
                'stock' => $p->stock,
                'photo' => $p->imagePrincipale?->chemin ?? $p->photo,
                'categorie' => $p->categorie?->nom,
                'categorie_id' => $p->categorie_id,
                'description_courte' => $p->description_courte,
                'featured' => (bool) $p->featured,
            ];
        });

        return view('boutique.index', [
            'institut' => $institut,
            'produits' => $produits,
            'produitsJson' => $produitsJson,
            'categories' => $categories,
            'ogTitle' => $institut->nom . ' - Boutique en ligne',
            'ogDescription' => 'Découvrez nos produits et passez commande en ligne avec livraison à domicile',
            'ogImage' => $institut->logo ? asset('storage/' . $institut->logo) : null,
        ]);
    }

    /**
     * Détails d'un produit
     */
    public function produit(string $slug, string $id)
    {
        $institut = Institut::where('slug', $slug)->firstOrFail();

        if (!$institut->boutique_active) {
            abort(404, 'Cette boutique n\'est pas disponible.');
        }

        // Vérifier l'accès boutique (abonnement)
        if (!$institut->proprietaire?->hasBoutiqueAccess()) {
            abort(404, 'Cette boutique n\'est pas disponible.');
        }

        $produit = Produit::where('id', $id)
            ->where('institut_id', $institut->id)
            ->where('actif', true)
            ->where(function ($q) {
                // Compatibilité : produits sans colonne visible_boutique (avant migration)
                $q->where('visible_boutique', true)->orWhereNull('visible_boutique');
            })
            ->with(['categorie', 'images'])
            ->firstOrFail();

        // Produits similaires (même catégorie)
        $produitsSimilaires = Produit::where('institut_id', $institut->id)
            ->where('categorie_id', $produit->categorie_id)
            ->where('id', '!=', $produit->id)
            ->where('actif', true)
            ->where('stock', '>', 0)
            ->limit(4)
            ->get();

        return view('boutique.produit', [
            'institut' => $institut,
            'produit' => $produit,
            'produitsSimilaires' => $produitsSimilaires,
            'ogTitle' => $produit->nom . ' - ' . $institut->nom,
            'ogDescription' => $produit->description ?? 'Commandez ce produit en ligne',
            'ogImage' => $produit->photo ? asset('storage/' . $produit->photo) : asset('storage/' . $institut->logo),
        ]);
    }

    /**
     * Page checkout (commander)
     */
    public function commanderForm(string $slug)
    {
        $institut = Institut::where('slug', $slug)->firstOrFail();

        if (!$institut->boutique_active) {
            abort(404, 'Cette boutique n\'est pas disponible.');
        }

        return view('boutique.commander', compact('institut'));
    }

    /**
     * Passer une commande
     */
    public function commander(Request $request, string $slug)
    {
        $institut = Institut::where('slug', $slug)->firstOrFail();

        if (!$institut->boutique_active) {
            return back()->with('error', 'Cette boutique n\'est pas disponible.');
        }

        // Validation
        $data = $request->validate([
            'prenom' => 'required|string|max:255',
            'nom' => 'required|string|max:255',
            'telephone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'adresse' => 'required|string|max:500',
            'notes' => 'nullable|string|max:1000',
            'mode_paiement' => 'nullable|string|max:30',
            'panier' => 'required|array|min:1',
            'panier.*.produit_id' => 'required|uuid|exists:produits,id',
            'panier.*.quantite' => 'required|integer|min:1|max:999',
        ]);

        try {
            DB::beginTransaction();

            // Récupérer tous les produits du panier
            $produitIds = collect($data['panier'])->pluck('produit_id');
            $produits = Produit::whereIn('id', $produitIds)
                ->where('institut_id', $institut->id)
                ->where('actif', true)
                ->get()
                ->keyBy('id');

            // Vérifier les stocks
            foreach ($data['panier'] as $item) {
                $produit = $produits->get($item['produit_id']);

                if (!$produit) {
                    throw new \Exception('Produit non trouvé ou indisponible.');
                }

                if ($produit->stock < $item['quantite']) {
                    throw new \Exception("Stock insuffisant pour {$produit->nom}. Disponible : {$produit->stock}");
                }
            }

            // Créer ou récupérer le client
            $client = Client::firstOrCreate(
                [
                    'telephone' => $data['telephone'],
                    'institut_id' => $institut->id,
                ],
                [
                    'prenom' => $data['prenom'],
                    'nom' => $data['nom'],
                    'email' => $data['email'],
                    'adresse' => $data['adresse'],
                ]
            );

            // Calculer les montants
            $sousTotal = 0;
            $items = [];

            foreach ($data['panier'] as $item) {
                $produit = $produits->get($item['produit_id']);
                $quantite = $item['quantite'];
                $prixUnitaire = $produit->prix_promo ?: $produit->prix_vente;
                $sousTotal += $prixUnitaire * $quantite;

                $items[] = [
                    'produit_id' => $produit->id,
                    'nom_snapshot' => $produit->nom,
                    'prix_snapshot' => $prixUnitaire,
                    'quantite' => $quantite,
                    'sous_total' => $prixUnitaire * $quantite,
                ];
            }

            $fraisLivraison = $institut->boutique_frais_livraison ?? 0;
            $total = $sousTotal + $fraisLivraison;

            // Créer la commande
            $commande = Commande::create([
                'institut_id' => $institut->id,
                'client_id' => $client->id,
                'client_prenom' => $data['prenom'],
                'client_nom' => $data['nom'],
                'client_telephone' => $data['telephone'],
                'client_email' => $data['email'] ?? null,
                'client_adresse' => $data['adresse'],
                'sous_total' => $sousTotal,
                'frais_livraison' => $fraisLivraison,
                'total' => $total,
                'statut' => 'nouvelle',
                'mode_paiement' => 'cash',
                'notes_client' => $data['notes'] ?? null,
            ]);

            // Créer les items de la commande
            foreach ($items as $itemData) {
                CommandeItem::create([
                    'commande_id' => $commande->id,
                    ...$itemData,
                ]);
            }

            DB::commit();

            // Envoyer les emails
            try {
                Mail::to($data['email'] ?? $data['telephone'] . '@temp.maelya.com')
                    ->send(new NouvelleCommandeClient($commande, $institut));

                if ($institut->email) {
                    Mail::to($institut->email)
                        ->send(new NouvelleCommandeEtablissement($commande, $institut));
                }
            } catch (\Exception $e) {
                \Log::error('Erreur envoi email commande: ' . $e->getMessage());
            }

            // Notifications aux admins
            try {
                $admins = $institut->users()->where('role', 'admin')->get();
                $notificationService = new NotificationService();
                $pushService = new PushNotificationService();

                foreach ($admins as $admin) {
                    $notificationService->notifyUser(
                        $admin,
                        'commande_nouvelle',
                        'Nouvelle commande',
                        "Commande {$commande->numero} de {$commande->client_prenom} {$commande->client_nom} - " . number_format($commande->total, 0, ',', ' ') . " FCFA",
                        route('dashboard.boutique.commandes.show', $commande)
                    );

                    $pushService->sendToUser(
                        $admin,
                        '🛒 Nouvelle commande boutique',
                        "Commande {$commande->numero} - " . number_format($commande->total, 0, ',', ' ') . " FCFA",
                        route('dashboard.boutique.commandes.show', $commande)
                    );
                }
            } catch (\Exception $e) {
                \Log::error('Erreur envoi notifications commande: ' . $e->getMessage());
            }

            return redirect()->route('shop.suivi', [
                'slug' => $slug,
                'numero' => $commande->numero
            ])->with('success', 'Votre commande a été passée avec succès !');

        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // Numéro de commande en doublon (race condition entre 2 instituts) — retry
            DB::rollBack();
            return back()->with('error', 'Une erreur technique est survenue. Merci de réessayer.')->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Suivi de commande
     */
    public function suivreCommande(string $slug, string $numero)
    {
        $institut = Institut::where('slug', $slug)->firstOrFail();

        $commande = Commande::where('numero', $numero)
            ->where('institut_id', $institut->id)
            ->with('items')
            ->firstOrFail();

        return view('boutique.suivi', [
            'institut' => $institut,
            'commande' => $commande,
        ]);
    }
}
