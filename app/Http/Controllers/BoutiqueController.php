<?php

namespace App\Http\Controllers;

use App\Mail\NouvelleCommandeClient;
use App\Mail\NouvelleCommandeEtablissement;
use App\Models\Client;
use App\Models\Commande;
use App\Models\CommandeItem;
use App\Models\Institut;
use App\Models\Produit;
use App\Scopes\InstitutScope;
use App\Services\NotificationService;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class BoutiqueController extends Controller
{
    /**
     * Vérifie l'accès public à la boutique d'un établissement.
     *
     * Utilise le propriétaire « effectif » (repli si proprietaire_id est NULL)
     * afin de ne plus renvoyer de 404 erronés sur les comptes anciens.
     */
    private function boutiqueAccessible(Institut $institut): bool
    {
        if (!$institut->boutique_active) {
            return false;
        }

        $proprietaire = $institut->proprietaireEffectif();

        // Aucun propriétaire identifiable → on se base sur l'option payée
        // de l'établissement (évite un 404 définitif sur données legacy).
        if (!$proprietaire) {
            return $institut->hasBoutiqueOption();
        }

        return $proprietaire->hasBoutiqueAccess($institut);
    }

    private function verifierAcces(Institut $institut): void
    {
        if (!$this->boutiqueAccessible($institut)) {
            abort(404, 'Cette boutique n\'est pas disponible.');
        }
    }

    /**
     * Requête de base des produits de la boutique PUBLIQUE.
     *
     * - `withoutGlobalScope` : un visiteur connecté (ou un gérant multi-établissements)
     *   ne doit jamais voir la liste filtrée par SON établissement courant.
     * - `visible_boutique` tolère NULL (produits legacy).
     * - `stock` tolère NULL (produits legacy).
     */
    private function produitsBoutique(Institut $institut, bool $exigerStock = true)
    {
        $query = Produit::withoutGlobalScope(InstitutScope::class)
            ->where('produits.institut_id', $institut->id)
            ->where('produits.actif', true)
            ->where(function ($q) {
                $q->whereNull('produits.visible_boutique')
                  ->orWhere('produits.visible_boutique', true);
            });

        if ($exigerStock) {
            $query->where(function ($q) {
                $q->whereNull('produits.stock')
                  ->orWhere('produits.stock', '>', 0);
            });
        }

        return $query;
    }

    /**
     * Page principale de la boutique d'un établissement
     */
    public function index(string $slug)
    {
        $institut = Institut::where('slug', $slug)->firstOrFail();

        $this->verifierAcces($institut);

        // Récupérer les produits directement (pas de cache — source de bugs)
        $produits = $this->produitsBoutique($institut)
            ->with(['categorie', 'imagePrincipale'])
            ->orderByDesc('featured')
            ->orderBy('nom')
            ->get();

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

        $this->verifierAcces($institut);

        $produit = $this->produitsBoutique($institut, false)
            ->where('produits.id', $id)
            ->with(['categorie', 'images'])
            ->first();

        if (!$produit) {
            return view('boutique.produit-indisponible', [
                'institut' => $institut,
                'ogTitle' => 'Produit indisponible - ' . $institut->nom,
            ]);
        }

        // Produits similaires (même catégorie)
        $produitsSimilaires = $this->produitsBoutique($institut)
            ->where('produits.categorie_id', $produit->categorie_id)
            ->where('produits.id', '!=', $produit->id)
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

        $this->verifierAcces($institut);

        $zones = is_array($institut->boutique_zones_livraison) ? $institut->boutique_zones_livraison : [];

        return view('boutique.commander', compact('institut', 'zones'));
    }

    /**
     * Passer une commande
     */
    public function commander(Request $request, string $slug)
    {
        $institut = Institut::where('slug', $slug)->firstOrFail();

        if (!$this->boutiqueAccessible($institut)) {
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
            'zone_index' => 'nullable|integer|min:0',
        ]);

        try {
            DB::beginTransaction();

            // Récupérer tous les produits du panier
            $produitIds = collect($data['panier'])->pluck('produit_id');
            $produits = $this->produitsBoutique($institut, false)
                ->whereIn('produits.id', $produitIds)
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

            // Créer ou récupérer le client — unicité par téléphone OU email
            // (sans le scope « établissement courant » : commande publique)
            $client = Client::withoutGlobalScope(InstitutScope::class)
                ->where('clients.institut_id', $institut->id)
                ->where(function ($q) use ($data) {
                    $q->where('telephone', $data['telephone']);
                    if (!empty($data['email'])) {
                        $q->orWhere('email', $data['email']);
                    }
                })
                ->first();

            if (!$client) {
                $client = Client::create([
                    'institut_id' => $institut->id,
                    'type_client' => 'personne_physique',
                    'prenom' => $data['prenom'],
                    'nom' => $data['nom'],
                    'telephone' => $data['telephone'],
                    'email' => $data['email'] ?? null,
                    'adresse' => $data['adresse'],
                ]);
            }

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

            // Calculer les frais de livraison selon la zone choisie
            $zones = is_array($institut->boutique_zones_livraison) ? $institut->boutique_zones_livraison : [];
            $fraisLivraison = $institut->boutique_frais_livraison ?? 0;
            if (isset($data['zone_index']) && isset($zones[$data['zone_index']])) {
                $fraisLivraison = (int) ($zones[$data['zone_index']]['frais'] ?? $fraisLivraison);
            }
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

            // Envoyer l'événement Purchase au Pixel Facebook
            \App\Jobs\SendFacebookPurchaseEvent::dispatch($commande);

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

        // Suivi public : ne pas appliquer le scope « établissement courant »
        $commande = Commande::withoutGlobalScope(InstitutScope::class)
            ->where('commandes.numero', $numero)
            ->where('commandes.institut_id', $institut->id)
            ->with('items')
            ->firstOrFail();

        return view('boutique.suivi', [
            'institut' => $institut,
            'commande' => $commande,
        ]);
    }
}
