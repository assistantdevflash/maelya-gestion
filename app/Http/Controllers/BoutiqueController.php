<?php

namespace App\Http\Controllers;

use App\Mail\LiensCommandesClient;
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
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
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

    /**
     * Coupe l'accès à une boutique non ouverte.
     *
     * On ne renvoie plus un 404 sec : un client qui a gardé le lien (ou un
     * client qui arrive depuis une conversation WhatsApp) tombait sur une page
     * d'erreur sans explication. La page « boutique fermée » indique la marche
     * à suivre et propose le téléphone / WhatsApp de l'établissement.
     */
    private function verifierAcces(Institut $institut): void
    {
        if ($this->boutiqueAccessible($institut)) {
            return;
        }

        throw new HttpResponseException(
            response()->view('boutique.fermee', [
                'institut' => $institut,
            ], 200)
        );
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
    public function index(Request $request, string $slug)
    {
        $institut = Institut::where('slug', $slug)->firstOrFail();

        $this->verifierAcces($institut);

        $recherche = trim((string) $request->input('q', ''));
        $categorie = $request->input('categorie');
        $tri       = (string) $request->input('tri', 'reco');

        $base = $this->produitsBoutique($institut);

        // Catégories réellement disponibles, avec leur nombre de produits
        $compteurs = (clone $base)
            ->selectRaw('categorie_id, COUNT(*) as total')
            ->groupBy('categorie_id')
            ->pluck('total', 'categorie_id');

        $categories = \App\Models\CategorieProduit::whereIn('id', $compteurs->keys())
            ->orderBy('nom')
            ->get();

        $query = (clone $base)
            ->with(['categorie', 'imagePrincipale'])
            ->when($recherche !== '', function ($q) use ($recherche) {
                // Recherche serveur : le lien reste partageable et indexable
                $q->where(function ($w) use ($recherche) {
                    $like = '%' . $recherche . '%';
                    $w->where('produits.nom', 'like', $like)
                      ->orWhere('produits.description_courte', 'like', $like)
                      ->orWhere('produits.description', 'like', $like);
                });
            })
            ->when($categorie, fn ($q) => $q->where('produits.categorie_id', $categorie));

        // Prix réellement facturé (promo prioritaire) pour les tris par prix
        $prixEffectif = 'COALESCE(NULLIF(produits.prix_promo, 0), produits.prix_vente)';

        $query = match ($tri) {
            'prix_asc'  => $query->orderByRaw($prixEffectif . ' ASC'),
            'prix_desc' => $query->orderByRaw($prixEffectif . ' DESC'),
            'nom'       => $query->orderBy('produits.nom'),
            default     => $query->orderByDesc('produits.featured')->orderBy('produits.nom'),
        };

        // Cache court : la version change dès qu'un produit est modifié
        $version = (clone $base)->max('produits.updated_at');
        $cle = 'boutique:' . md5(implode('|', [
            $institut->id, $version, $recherche, (string) $categorie, $tri, (string) $request->input('page', 1),
        ]));

        $produits = Cache::remember($cle, now()->addSeconds(60), fn () => $query->paginate(24)->withQueryString());

        // Données du panier : uniquement la page affichée
        $produitsJson = $produits->getCollection()->map(fn ($p) => [
            'id'                 => $p->id,
            'nom'                => $p->nom,
            'prix'               => $p->prix_promo ?: $p->prix_vente,
            'prix_vente'         => $p->prix_vente,
            'prix_promo'         => $p->prix_promo,
            'stock'              => $p->stock,
            'photo'              => $p->imagePrincipale?->chemin ?? $p->photo,
            'categorie'          => $p->categorie?->nom,
            'categorie_id'       => $p->categorie_id,
            'description_courte' => $p->description_courte,
            'featured'           => (bool) $p->featured,
        ]);

        return view('boutique.index', [
            'institut'      => $institut,
            'produits'      => $produits,
            'produitsJson'  => $produitsJson,
            'categories'    => $categories,
            'compteurs'     => $compteurs,
            'recherche'     => $recherche,
            'categorieActive' => $categorie,
            'tri'           => $tri,
            'ogTitle'       => $institut->nom . ' - Boutique en ligne',
            'ogDescription' => 'Découvrez nos produits et passez commande en ligne avec livraison à domicile',
            'ogImage'       => $institut->logo ? asset('storage/' . $institut->logo) : null,
            // La catégorie et la page restent indexables, pas la recherche ni
            // le tri (contenu dupliqué).
            'canonical'     => route('shop.index', array_filter([
                'slug'      => $institut->slug,
                'categorie' => $categorie,
                'page'      => $request->input('page'),
            ])),
            'robots'        => $recherche !== '' || $tri !== 'reco'
                ? 'noindex, follow'
                : 'index, follow',
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
            ->with('imagePrincipale')
            ->orderByDesc('produits.featured')
            ->orderBy('produits.nom')
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
     * Moyen de paiement utilisable au checkout de la boutique.
     * Piloté par le super admin (payment_methods.boutique_checkout_enabled),
     * et subordonné à la présence des identifiants du prestataire.
     */
    private function moyenPaiementEnLigne(): ?\App\Models\PaymentMethod
    {
        if (! config('services.geniuspay.api_key')) {
            return null;
        }

        return \App\Models\PaymentMethod::pourCheckoutBoutique()
            ->where('code', 'geniuspay')
            ->first();
    }

    public function commanderForm(string $slug)
    {
        $institut = Institut::where('slug', $slug)->firstOrFail();

        $this->verifierAcces($institut);

        $zones = is_array($institut->boutique_zones_livraison) ? $institut->boutique_zones_livraison : [];

        $paiementEnLigneDisponible = $this->moyenPaiementEnLigne() !== null;

        // Catalogue réduit (id → prix/stock/nom) : le panier stocké dans le
        // navigateur peut contenir des articles supprimés, remis en stock ou
        // dont le prix a changé depuis la dernière visite.
        $catalogue = $this->produitsBoutique($institut, false)
            ->select(['produits.id', 'produits.nom', 'produits.prix_vente', 'produits.prix_promo', 'produits.stock'])
            ->get()
            ->mapWithKeys(fn ($p) => [$p->id => [
                'nom'   => $p->nom,
                'prix'  => (int) ($p->prix_promo ?: $p->prix_vente),
                'stock' => $p->stock === null ? null : (int) $p->stock,
            ]]);

        return view('boutique.commander', compact(
            'institut', 'zones', 'paiementEnLigneDisponible', 'catalogue'
        ));
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
            'date_livraison_prevue' => 'nullable|date|after_or_equal:today',
            'creneau_livraison' => 'nullable|string|max:40',
            'paiement_en_ligne' => 'nullable|boolean',
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
                // Le mode de paiement n'est jamais dicté par le formulaire :
                // une commande est réglée à la livraison. Le paiement en ligne,
                // s'il est activé, bascule ce champ une fois le règlement reçu.
                'mode_paiement' => 'cash',
                'notes_client' => $data['notes'] ?? null,
                'date_livraison_prevue' => $data['date_livraison_prevue'] ?? null,
                'creneau_livraison' => $data['creneau_livraison'] ?? null,
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

            // Paiement en ligne : le client règle maintenant, la commande est
            // encaissée dès la confirmation du prestataire.
            $methode = $this->moyenPaiementEnLigne();
            $paiementEnLigne = (bool) ($data['paiement_en_ligne'] ?? false) && $methode;

            if ($paiementEnLigne) {
                {
                    $retourSucces = \Illuminate\Support\Facades\URL::signedRoute('shop.paiement.retour', [
                        'slug'   => $slug,
                        'numero' => $commande->numero,
                        'etat'   => 'succes',
                    ]);

                    $transaction = \App\Models\PaymentTransaction::create([
                        'id'                  => (string) \Illuminate\Support\Str::uuid(),
                        'reference'           => \App\Models\PaymentTransaction::generateReference(),
                        'user_id'             => $institut->proprietaire_id ?? $institut->users()->value('id'),
                        'institut_id'         => $institut->id,
                        'type'                => 'commande',
                        'amount'              => (int) $commande->total,
                        'net_amount'          => (int) $commande->total,
                        'currency'            => 'XOF',
                        'payment_method_id'   => $methode->id,
                        'payment_method_code' => 'geniuspay',
                        'status'              => 'pending',
                        'metadata'            => [
                            'commande_id' => $commande->id,
                            'numero'      => $commande->numero,
                            'success_url' => $retourSucces,
                            'error_url'   => \Illuminate\Support\Facades\URL::signedRoute('shop.paiement.retour', [
                                'slug'   => $slug,
                                'numero' => $commande->numero,
                                'etat'   => 'echec',
                            ]),
                        ],
                    ]);

                    try {
                        $result = app(\App\Services\PaymentGatewayManager::class)->initiate($transaction, $methode);

                        return redirect()->away($result['checkout_url']);
                    } catch (\Throwable $e) {
                        $transaction->update(['status' => 'failed']);
                        \Illuminate\Support\Facades\Log::error('[Boutique] paiement en ligne indisponible : ' . $e->getMessage());
                        // La commande reste valable en paiement à la livraison
                    }
                }
            }

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

            // Compteurs de la liste rafraîchis immédiatement
            \Illuminate\Support\Facades\Cache::forget("cmd_stats:{$institut->id}");

            return redirect(\Illuminate\Support\Facades\URL::signedRoute('shop.suivi', [
                'slug' => $slug,
                'numero' => $commande->numero,
            ]))->with('success', 'Votre commande a été passée avec succès !');

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
     * Retour du prestataire de paiement (lien signé).
     */
    public function retourPaiement(Request $request, string $slug, string $numero, string $etat)
    {
        $institut = Institut::where('slug', $slug)->firstOrFail();

        $commande = Commande::withoutGlobalScope(InstitutScope::class)
            ->where('commandes.numero', $numero)
            ->where('commandes.institut_id', $institut->id)
            ->firstOrFail();

        if ($etat === 'succes') {
            // Le webhook peut arriver après le retour : on vérifie directement.
            $transaction = \App\Models\PaymentTransaction::where('type', 'commande')
                ->where('metadata->commande_id', $commande->id)
                ->latest()
                ->first();

            if ($transaction && $transaction->isPending()) {
                try {
                    app(\App\Services\PaymentGatewayManager::class)->verify($transaction);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('[Boutique] vérification paiement : ' . $e->getMessage());
                }
            }

            $commande->refresh();

            return redirect(\Illuminate\Support\Facades\URL::signedRoute('shop.suivi', [
                'slug'   => $slug,
                'numero' => $commande->numero,
            ]))->with(
                $commande->payee ? 'success' : 'error',
                $commande->payee
                    ? 'Paiement reçu, merci ! Votre commande est confirmée.'
                    : 'Paiement en cours de vérification : votre commande reste enregistrée.'
            );
        }

        return redirect(\Illuminate\Support\Facades\URL::signedRoute('shop.suivi', [
            'slug'   => $slug,
            'numero' => $commande->numero,
        ]))->with('error', 'Le paiement n\'a pas abouti. Votre commande est conservée : vous pouvez régler à la livraison.');
    }

    /**
     * Annulation par le client depuis son lien de suivi signé.
     * Le stock réservé est immédiatement remis en rayon.
     */
    public function annulerParClient(string $slug, string $numero)
    {
        $institut = Institut::where('slug', $slug)->firstOrFail();

        $commande = Commande::withoutGlobalScope(InstitutScope::class)
            ->where('commandes.numero', $numero)
            ->where('commandes.institut_id', $institut->id)
            ->firstOrFail();

        if (! $commande->peutEtreAnnuleeParClient()) {
            return back()->with('error', 'Cette commande ne peut plus être annulée. Contactez l\'établissement.');
        }

        app(\App\Services\CommandeService::class)
            ->annuler($commande, 'annulee', 'Annulée par le client', 'client');

        \Illuminate\Support\Facades\Cache::forget("cmd_stats:{$institut->id}");

        return back()->with('success', 'Votre commande a bien été annulée.');
    }

    /**
     * Suivi de commande
     */
    /**
     * Formulaire de récupération des liens de suivi.
     */
    public function formulaireCommandes(string $slug)
    {
        $institut = Institut::where('slug', $slug)->firstOrFail();

        $this->verifierAcces($institut);

        return view('boutique.retrouver-commandes', ['institut' => $institut]);
    }

    /**
     * Renvoie par email les liens de suivi des commandes d'un client.
     *
     * Le client n'a pas de compte : on ne peut pas l'authentifier. La parade
     * est de ne RIEN afficher à l'écran (aucune énumération possible) et de
     * n'envoyer les liens que sur l'email déjà enregistré sur la commande.
     */
    public function envoyerLiensCommandes(Request $request, string $slug)
    {
        $institut = Institut::where('slug', $slug)->firstOrFail();

        $this->verifierAcces($institut);

        $data = $request->validate([
            'contact' => 'required|string|min:3|max:255',
        ]);

        // Message strictement identique qu'il y ait des commandes ou non :
        // il ne doit pas permettre de deviner quels numéros sont clients.
        $message = 'Si des commandes correspondent à ces informations, un email vient de vous être envoyé.';

        try {
            $commandes = $this->commandesPourContact($institut, trim($data['contact']));

            $commandes
                ->filter(fn (Commande $c) => filter_var((string) $c->client_email, FILTER_VALIDATE_EMAIL))
                ->groupBy(fn (Commande $c) => mb_strtolower((string) $c->client_email))
                ->each(function (Collection $groupe, string $email) use ($institut) {
                    Mail::to($email)->send(new LiensCommandesClient($institut, $groupe));
                });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error(
                '[Boutique] renvoi des liens de commande : ' . $e->getMessage()
            );
        }

        return redirect()
            ->route('shop.commandes.form', $institut->slug)
            ->with('success', $message);
    }

    /**
     * Commandes d'un établissement correspondant à un email ou un téléphone.
     *
     * Le téléphone est comparé sur les 8 derniers chiffres : cela tolère les
     * formats « 07 01 02 03 04 », « +2250701020304 » ou « 0701020304 ».
     *
     * @return Collection<int, Commande>
     */
    private function commandesPourContact(Institut $institut, string $contact): Collection
    {
        $query = Commande::withoutGlobalScope(InstitutScope::class)
            ->where('commandes.institut_id', $institut->id)
            ->orderByDesc('commandes.created_at')
            ->limit(20);

        if (str_contains($contact, '@')) {
            return $query
                ->whereRaw('LOWER(commandes.client_email) = ?', [mb_strtolower($contact)])
                ->get();
        }

        $chiffres = preg_replace('/\D+/', '', $contact);

        // Moins de 8 chiffres : la recherche serait trop large pour être fiable
        if (strlen((string) $chiffres) < 8) {
            return collect();
        }

        $fin = substr($chiffres, -8);

        return $query->whereRaw(
            "REPLACE(REPLACE(REPLACE(REPLACE(commandes.client_telephone,' ',''),'-',''),'.',''),'+','') LIKE ?",
            ['%' . $fin . '%']
        )->get();
    }

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
