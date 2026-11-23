<?php

namespace App\Http\Controllers;

use App\Helpers\SeoHelper;
use App\Models\EtablissementType;
use App\Models\MessageContact;
use App\Models\PlanAbonnement;
use App\Services\NotificationService;
use App\Services\PushNotificationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class LandingController extends Controller
{
    /**
     * Longueur maximale d'une méta description avant troncature par Google.
     */
    private const LONGUEUR_DESCRIPTION = 158;

    public function index()
    {
        $plans = PlanAbonnement::where('actif', true)->orderBy('ordre')->get();

        // Les types d'établissement pilotent le référencement : la liste
        // affichée et les mots-clés suivent ce qui est actif dans
        // l'administration, sans intervention dans le code.
        $types = EtablissementType::pourSeo();

        return view('landing.index', [
            'plans' => $plans,
            'types' => $types,
            'title' => 'Logiciel de gestion pour indépendants, PME et entreprises de service',
            'metaDescription' => $this->descriptionAccueil($types),
        ]);
    }

    /**
     * Page publique dédiée à un type d'établissement.
     *
     * C'est cette page — et non la page d'accueil — qui se positionne sur les
     * recherches du type « logiciel de gestion salon de coiffure ».
     */
    public function secteur(string $slug)
    {
        $type = EtablissementType::trouverParSlug($slug);

        // Un type désactivé ou supprimé ne doit pas rester accessible : son URL
        // renvoie une page introuvable plutôt qu'un contenu périmé.
        abort_if($type === null, 404);

        $contenu = config('secteurs.' . $type->code) ?? config('secteurs._defaut');
        $libelleMinuscule = mb_strtolower($type->libelle);

        $autres = EtablissementType::pourSeo()
            ->reject(fn (EtablissementType $autre) => $autre->id === $type->id)
            ->values();

        $description = 'Caisse, rendez-vous, clients, stocks et finances pour '
            . $libelleMinuscule
            . '. Maëlya Gestion, logiciel de gestion pensé pour la Côte d\'Ivoire. Essai gratuit 14 jours.';

        $questionsCommunes = $this->questionsCommunes($libelleMinuscule);

        return view('landing.secteur', [
            'type' => $type,
            'contenu' => $contenu,
            'autres' => $autres,
            'titre' => 'Logiciel de gestion pour ' . $libelleMinuscule,
            'title' => 'Logiciel de gestion pour ' . $libelleMinuscule,
            'metaDescription' => $description,
            'questionsCommunes' => $questionsCommunes,
            'jsonLd' => $this->donneesStructureesSecteur($type, $contenu, $libelleMinuscule, $description, $questionsCommunes),
        ]);
    }

    /**
     * Méta description de l'accueil, construite à partir des types actifs.
     *
     * On ajoute les libellés tant que la longueur utile le permet, puis on
     * s'arrête : une description trop longue est coupée par Google au milieu
     * d'un mot.
     */
    private function descriptionAccueil(Collection $types): string
    {
        $prefixe = 'Logiciel de gestion pour ';
        $suffixe = '. Caisse, stocks et finances réunis. Essai gratuit 14 jours.';
        $noms = $types->map(fn (EtablissementType $type) => mb_strtolower($type->libelle))->all();

        for ($nombre = count($noms); $nombre >= 1; $nombre--) {
            $liste = implode(', ', array_slice($noms, 0, $nombre));

            if ($nombre < count($noms)) {
                $liste .= ' et bien d\'autres';
            }

            $candidat = $prefixe . $liste . $suffixe;

            if (mb_strlen($candidat) <= self::LONGUEUR_DESCRIPTION) {
                return $candidat;
            }
        }

        return 'Logiciel de gestion des indépendants et PME de service' . $suffixe;
    }

    /**
     * Données structurées de la page secteur : service, fil d'Ariane et FAQ.
     */
    private function donneesStructureesSecteur(
        EtablissementType $type,
        array $contenu,
        string $libelleMinuscule,
        string $description,
        array $questionsCommunes
    ): array {
        $jsonLd = [
            [
                '@context' => 'https://schema.org',
                '@type' => 'Service',
                'name' => 'Logiciel de gestion pour ' . $libelleMinuscule,
                'serviceType' => 'Logiciel de gestion',
                'description' => $description,
                'url' => route('secteur', $type->slug),
                'image' => SeoHelper::imageDefaut(),
                'areaServed' => ['@type' => 'Country', 'name' => 'Côte d\'Ivoire'],
                'provider' => [
                    '@type' => 'Organization',
                    'name' => 'Maëlya Gestion',
                    'url' => url('/'),
                ],
                'offers' => [
                    '@type' => 'Offer',
                    'price' => '0',
                    'priceCurrency' => 'XOF',
                    'description' => 'Essai gratuit de 14 jours, sans engagement',
                ],
            ],
            [
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    [
                        '@type' => 'ListItem',
                        'position' => 1,
                        'name' => 'Accueil',
                        'item' => route('home'),
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 2,
                        'name' => $type->libelle,
                        'item' => route('secteur', $type->slug),
                    ],
                ],
            ],
        ];

        $questions = [];

        if (filled($contenu['question'] ?? null) && filled($contenu['reponse'] ?? null)) {
            $questions[] = [
                '@type' => 'Question',
                'name' => $contenu['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $contenu['reponse']],
            ];
        }

        foreach ($questionsCommunes as [$question, $reponse]) {
            $questions[] = [
                '@type' => 'Question',
                'name' => $question,
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $reponse],
            ];
        }
        $jsonLd[] = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $questions,
        ];

        return $jsonLd;
    }

    /**
     * Questions posées pour chaque secteur.
     */
    public function questionsCommunes(string $libelleMinuscule): array
    {
        return [
            [
                'Combien de temps faut-il pour démarrer ?',
                'Moins de deux minutes : vous créez votre compte, vous saisissez le nom de votre '
                    . $libelleMinuscule
                    . ' et vous commencez à encaisser. L\'essai est gratuit pendant 14 jours, sans carte bancaire.',
            ],
            [
                'Maëlya Gestion fonctionne-t-il sur téléphone ?',
                'Oui. L\'application est conçue d\'abord pour le mobile et s\'installe sur l\'écran d\'accueil du téléphone, comme une application classique.',
            ],
            [
                'Mes données sont-elles visibles par d\'autres établissements ?',
                'Non. Chaque établissement dispose de son propre espace cloisonné. Vos clients, vos ventes et vos finances ne sont accessibles qu\'aux utilisateurs que vous avez créés.',
            ],
        ];
    }

    public function apropos()
    {
        return view('landing.apropos', [
            'title' => 'À propos',
            'metaDescription' => 'Découvrez Maëlya Gestion, la solution conçue pour simplifier la gestion des établissements de service en Côte d\'Ivoire. Notre mission : vous faire gagner du temps.',
        ]);
    }

    public function faq()
    {
        return view('landing.faq', [
            'title' => 'Questions fréquentes',
            'metaDescription' => 'Trouvez les réponses à vos questions sur Maëlya Gestion : inscription, abonnements, fonctionnalités et sécurité.',
        ]);
    }

    public function contact()
    {
        return view('landing.contact', [
            'title' => 'Contact',
            'metaDescription' => 'Contactez l\'équipe Maëlya Gestion. Nous répondons sous 24h. Email, WhatsApp ou formulaire de contact.',
        ]);
    }

    public function sendContact(Request $request)
    {
        // Leurre : un visiteur humain ne remplit jamais ce champ.
        // On répond un succès sans rien enregistrer, pour ne pas informer le robot.
        if ($request->filled('website')) {
            return redirect()->route('contact')->with('success', 'Message envoyé !');
        }

        // `dns` vérifie que le domaine a un enregistrement MX/A : efficace contre
        // les adresses inventées, mais impossible à exécuter hors ligne (tests/CI).
        $regleEmail = app()->environment('testing') ? 'email:rfc' : 'email:rfc,dns';

        $data = $request->validate([
            'nom'       => ['required', 'string', 'max:150', 'not_regex:/[\r\n<>{}|]/'],
            'email'     => ['required', $regleEmail, 'max:255', 'not_regex:/[\r\n]/'],
            'telephone' => ['nullable', 'string', 'max:30', 'regex:/^[+\d\s().\-]*$/'],
            'message'   => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'nom.required'      => 'Votre nom est requis.',
            'nom.not_regex'     => 'Le nom contient des caractères non autorisés.',
            'email.required'    => 'Votre email est requis.',
            'email.not_regex'   => 'L\'adresse email contient des caractères non autorisés.',
            'telephone.regex'   => 'Le numéro de téléphone contient des caractères non autorisés.',
            'message.required'  => 'Votre message est requis.',
            'message.min'       => 'Votre message doit contenir au moins 10 caractères.',
        ]);

        // Sanitisation : supprimer toute balise HTML résiduelle
        $nom     = Str::limit(strip_tags($data['nom']), 150);
        $message = Str::limit(strip_tags($data['message']), 2000);
        $email   = filter_var($data['email'], FILTER_SANITIZE_EMAIL);

        // ── Détection de spam (score cumulatif) ────────────────────────────
        $analyse = app(\App\Services\AntiSpamService::class)->analyser([
            'nom'     => $nom,
            'email'   => $email,
            'message' => $message,
            'website' => $request->input('website'),
            '_t'      => $request->input('_t'),
        ], (string) $request->ip());

        // Rafale depuis la même adresse email
        $dejaEnvoyes = MessageContact::where('email', $email)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        if ($dejaEnvoyes >= 3) {
            $analyse['spam'] = true;
            $analyse['score'] += 40;
            $analyse['raisons'][] = 'Plus de 3 messages en 24 h';
        }

        $contact = MessageContact::create([
            'nom'        => $nom,
            'email'      => $email,
            'telephone'  => $data['telephone'] ?? null,
            'message'    => $message,
            'honeypot'   => $request->input('website'),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
            'est_spam'   => $analyse['spam'],
            'spam_score' => min(255, $analyse['score']),
            'spam_raison' => $analyse['raisons']
                ? Str::limit(implode(' · ', $analyse['raisons']), 250)
                : null,
            // Un spam ne doit pas gonfler le compteur de non-lus
            'lu' => $analyse['spam'],
        ]);

        // ── Notification des super-admins (in-app + push) ──────────────────
        // Uniquement pour les messages légitimes : notifier à chaque robot
        // reviendrait à déplacer le spam du formulaire vers le téléphone.
        if (!$analyse['spam']) {
            $this->notifierNouveauMessage($contact);
        }

        // Réponse identique dans tous les cas : ne jamais révéler la détection
        return redirect()->route('contact')
            ->with('success', 'Votre message a bien été envoyé ! Nous vous répondrons sous 24h.');
    }

    /**
     * Prévient les super-admins qu'un message de contact vient d'arriver.
     *
     * Le formulaire est public : aucune erreur de notification ne doit
     * empêcher l'enregistrement ni renvoyer une erreur au visiteur.
     */
    private function notifierNouveauMessage(MessageContact $contact): void
    {
        $url   = route('admin.messages.show', $contact);
        $titre = '📩 Nouveau message de contact';
        $corps = Str::limit($contact->nom . ' : ' . $contact->message, 120);

        try {
            NotificationService::notifyAdmins('nouveau_message', $titre, $corps, $url);
        } catch (\Throwable $e) {
            Log::warning('[Notif message contact] ' . $e->getMessage());
        }

        try {
            app(PushNotificationService::class)->sendToAdmins($titre, $corps, $url);
        } catch (\Throwable $e) {
            Log::warning('[Push message contact] ' . $e->getMessage());
        }
    }

    public function mentionsLegales()
    {
        return view('landing.mentions', [
            'title' => 'Mentions légales',
            'metaDescription' => 'Mentions légales de Maëlya Gestion : éditeur, hébergement, propriété intellectuelle et protection des données.',
            'noindex' => true,
        ]);
    }

    public function sitemap()
    {
        $pages = [
            ['url' => route('home'),     'priority' => '1.0', 'changefreq' => 'weekly', 'lastmod' => now()->toIso8601String()],
            ['url' => route('about'),    'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => now()->toIso8601String()],
            ['url' => route('faq'),      'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => now()->toIso8601String()],
            ['url' => route('contact'),  'priority' => '0.7', 'changefreq' => 'monthly', 'lastmod' => now()->toIso8601String()],
        ];

        // Une entrée par type d'établissement publié : ce sont les pages qui
        // captent les recherches par métier.
        foreach (EtablissementType::pourSeo() as $type) {
            $pages[] = [
                'url' => route('secteur', $type->slug),
                'priority' => '0.9',
                'changefreq' => 'monthly',
                'lastmod' => $type->updated_at?->toIso8601String() ?? now()->toIso8601String(),
            ];
        }

        // Ajouter toutes les pages vitrine actives
        $instituts = \App\Models\Institut::where('vitrine_active', true)
            ->where('actif', true)
            ->get(['slug', 'updated_at']);

        foreach ($instituts as $institut) {
            $pages[] = [
                'url' => route('vitrine.show', $institut->slug),
                'priority' => '0.8',
                'changefreq' => 'daily',
                'lastmod' => $institut->updated_at->toIso8601String(),
            ];
        }

        // Ajouter toutes les boutiques en ligne actives + pages produits + checkout
        $boutiques = \App\Models\Institut::where('boutique_active', true)
            ->where('actif', true)
            ->with(['produits' => function ($q) {
                $q->where('actif', true)->where('visible_boutique', true)->where('stock', '>', 0);
            }])
            ->get(['id', 'slug', 'updated_at']);

        foreach ($boutiques as $institut) {
            // Page principale boutique
            $pages[] = [
                'url' => route('shop.index', $institut->slug),
                'priority' => '0.9',
                'changefreq' => 'daily',
                'lastmod' => $institut->updated_at->toIso8601String(),
            ];

            // Page checkout
            $pages[] = [
                'url' => route('shop.commander.form', $institut->slug),
                'priority' => '0.6',
                'changefreq' => 'monthly',
                'lastmod' => $institut->updated_at->toIso8601String(),
            ];

            // Pages produits individuelles
            foreach ($institut->produits as $produit) {
                $pages[] = [
                    'url' => route('shop.produit', [$institut->slug, $produit->id]),
                    'priority' => '0.7',
                    'changefreq' => 'weekly',
                    'lastmod' => $produit->updated_at->toIso8601String(),
                ];
            }
        }

        return response()
            ->view('landing.sitemap', compact('pages'))
            ->header('Content-Type', 'application/xml');
    }
}
