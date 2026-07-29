# Intégration Facebook — Boutique en ligne Maëlya Gestion

> **Version :** 1.0 — 29 juillet 2026  
> **Statut :** Proposition — En attente de validation

---

## Table des matières

1. [Architecture générale](#1-architecture-générale)
2. [Base de données](#2-base-de-données)
3. [Pixel Meta — Côté navigateur](#3-pixel-meta--côté-navigateur)
4. [Conversions API — Côté serveur](#4-conversions-api--côté-serveur)
5. [Interface utilisateur — Configuration](#5-interface-utilisateur--configuration)
6. [Dashboard statistique](#6-dashboard-statistique)
7. [Parcours utilisateur](#7-parcours-utilisateur)
8. [Sécurité & conformité](#8-sécurité--conformité)
9. [Plan de déploiement](#9-plan-de-déploiement)
10. [Checklist pré-lancement](#10-checklist-pré-lancement)

---

## 1. Architecture générale

```
┌──────────────────────────────────────────────────────────────────┐
│                     MAËLYA GESTION                               │
│                                                                  │
│  ┌─────────────┐    ┌─────────────┐    ┌─────────────────────┐  │
│  │ Boutique    │    │ Laravel     │    │ Base de données     │  │
│  │ publique    │───▶│ Job Queue   │───▶│ boutique_configs    │  │
│  │ /shop/{slug}│    │ (CAPI)      │    │ └─ facebook_pixel_id│  │
│  │             │    │             │    │ └─ facebook_token   │  │
│  │ fbq('init') │    │ POST /event │    │ └─ facebook_test_code│  │
│  └─────────────┘    └──────┬──────┘    └─────────────────────┘  │
│                            │                                      │
│                            ▼                                      │
│                   ┌─────────────────┐                            │
│                   │  Facebook CAPI  │                            │
│                   │  graph.facebook │                            │
│                   │  .com/v17.0/    │                            │
│                   │  {pixel}/events │                            │
│                   └─────────────────┘                            │
└──────────────────────────────────────────────────────────────────┘
```

### Double envoi

| Canal | Méthode | Usage |
|---|---|---|
| 🌐 **Navigateur** | `fbq()` JS | Tous les événements temps réel |
| 🖥️ **Serveur (CAPI)** | HTTP POST | `Purchase` + `InitiateCheckout` (fiabilité) |

Pourquoi les deux ? Le navigateur peut être bloqué (adblock, ITP Safari). Le serveur, lui, envoie toujours. Meta déduplique automatiquement via `event_id`.

---

## 2. Base de données

### Migration : `add_facebook_to_boutique_configs`

```php
Schema::table('boutique_configs', function (Blueprint $table) {
    $table->string('facebook_pixel_id')->nullable()->after('slug');
    $table->text('facebook_access_token')->nullable()->after('facebook_pixel_id');
    $table->string('facebook_test_code')->nullable()->after('facebook_access_token');
    $table->string('facebook_pixel_name')->nullable()->after('facebook_test_code');
    $table->timestamp('facebook_connected_at')->nullable()->after('facebook_pixel_name');
});
```

### Modèle `BoutiqueConfig`

```php
protected $casts = [
    'facebook_access_token' => 'encrypted',  // critique : chiffré en base
    'facebook_connected_at' => 'datetime',
];
```

---

## 3. Pixel Meta — Côté navigateur

### 3.1 Script dans le `<head>` de la boutique publique

Fichier : `resources/views/boutique/layouts/public.blade.php`

```html
@if($boutiqueConfig->facebook_pixel_id)
<!-- Meta Pixel Code -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '{{ $boutiqueConfig->facebook_pixel_id }}');
@if($boutiqueConfig->facebook_test_code)
fbq('set', 'fbp', null); // reset pour test
fbq('init', '{{ $boutiqueConfig->facebook_pixel_id }}', {
    external_id: 'test-{{ $boutiqueConfig->facebook_test_code }}'
});
@endif
fbq('track', 'PageView');
</script>
<noscript>
<img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id={{ $boutiqueConfig->facebook_pixel_id }}&ev=PageView&noscript=1"/>
</noscript>
<!-- End Meta Pixel Code -->
@endif
```

### 3.2 Événements déclenchés dans les vues Blade

| Événement | Déclencheur | Page |
|---|---|---|
| `PageView` | Chargement de toute page boutique | Toutes |
| `ViewCategory` | Page liste des produits | `shop/{slug}` |
| `ViewContent` | Page détail produit | `shop/{slug}/produit/{id}` |
| `Search` | Recherche produit | `shop/{slug}` avec `?q=` |
| `AddToCart` | Clic "Ajouter au panier" | `shop/{slug}/produit/{id}` |
| `RemoveFromCart` | Retrait du panier | Page panier |
| `InitiateCheckout` | Clic "Commander" | `shop/{slug}/commander` |
| `AddPaymentInfo` | Choix du mode de paiement | `shop/{slug}/commander` |
| `Purchase` | Commande confirmée | Page confirmation |
| `CompleteRegistration` | Création compte client | Inscription |
| `Contact` | Envoi formulaire contact | Page contact |
| `Lead` | Demande de rappel/devis | Formulaire |

### Exemple : `AddToCart`

```blade
<button onclick="fbq('track', 'AddToCart', {
    content_name: '{{ $produit->nom }}',
    content_ids: ['{{ $produit->id }}'],
    content_type: 'product',
    value: {{ $produit->prix }},
    currency: 'XOF'
})">
    Ajouter au panier
</button>
```

---

## 4. Conversions API — Côté serveur

### 4.1 Service Laravel : `app/Services/FacebookCapiService.php`

```php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookCapiService
{
    private string $pixelId;
    private string $accessToken;
    private ?string $testCode;

    public function __construct(string $pixelId, string $accessToken, ?string $testCode = null)
    {
        $this->pixelId     = $pixelId;
        $this->accessToken = $accessToken;
        $this->testCode    = $testCode;
    }

    /**
     * Envoie un événement à la Conversions API Meta.
     */
    public function sendEvent(string $eventName, array $userData, array $customData, ?string $eventId = null): bool
    {
        if (empty($this->pixelId) || empty($this->accessToken)) {
            return false;
        }

        $eventId ??= (string) \Illuminate\Support\Str::uuid();

        $payload = [
            'data' => [[
                'event_name'  => $eventName,
                'event_time'  => now()->timestamp,
                'event_id'    => $eventId,
                'event_source_url' => request()->fullUrl(),
                'action_source' => 'website',
                'user_data'   => $userData,
                'custom_data' => $customData,
            ]],
        ];

        // Mode test Meta
        if ($this->testCode) {
            $payload['test_event_code'] = $this->testCode;
        }

        $response = Http::timeout(5)
            ->post("https://graph.facebook.com/v21.0/{$this->pixelId}/events?access_token={$this->accessToken}", $payload);

        if ($response->failed()) {
            Log::warning('[FacebookCAPI] Échec envoi', [
                'event'    => $eventName,
                'status'   => $response->status(),
                'body'     => $response->body(),
            ]);
            return false;
        }

        return true;
    }
}
```

### 4.2 Événements serveur envoyés automatiquement

Envoyés via un **job Laravel** après chaque commande :

| Événement | Données transmises |
|---|---|
| `Purchase` | `value`, `currency: 'XOF'`, `content_ids`, `content_type: 'product'`, `num_items` |
| `InitiateCheckout` | `value`, `currency`, `content_ids`, `num_items` |

### 4.3 Job `SendFacebookPurchaseEvent`

```php
namespace App\Jobs;

use App\Models\Commande;
use App\Models\BoutiqueConfig;
use App\Services\FacebookCapiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendFacebookPurchaseEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private Commande $commande
    ) {}

    public function handle(): void
    {
        $config = BoutiqueConfig::where('institut_id', $this->commande->institut_id)->first();
        
        if (!$config || !$config->facebook_pixel_id || !$config->facebook_access_token) {
            return;
        }

        $capi = new FacebookCapiService(
            $config->facebook_pixel_id,
            $config->facebook_access_token,
            $config->facebook_test_code
        );

        $client = $this->commande->client;

        $userData = [
            'client_ip_address' => request()->ip(),
            'client_user_agent' => request()->userAgent(),
            'em' => $client?->email ? hash('sha256', $client->email) : null,
            'ph' => $client?->telephone ? hash('sha256', preg_replace('/\D/', '', $client->telephone)) : null,
            'fn' => $client?->prenom ? hash('sha256', mb_strtolower($client->prenom)) : null,
            'ln' => $client?->nom ? hash('sha256', mb_strtolower($client->nom)) : null,
            'external_id' => (string) ($client?->id ?? ''),
        ];

        $items = $this->commande->items;
        $customData = [
            'value'        => (float) $this->commande->total,
            'currency'     => 'XOF',
            'content_ids'  => $items->pluck('produit_id')->filter()->toArray(),
            'content_type' => 'product',
            'num_items'    => $items->sum('quantite'),
        ];

        $capi->sendEvent('Purchase', $userData, $customData);
    }
}
```

### 4.4 Déclenchement

Dans `CommandeController` après création d'une commande :

```php
Commande::created(function (Commande $commande) {
    SendFacebookPurchaseEvent::dispatch($commande);
});
```

---

## 5. Interface utilisateur — Configuration

### 5.1 Route

```php
// Dans routes/web.php, groupe dashboard
Route::prefix('boutique')->name('boutique.')->group(function () {
    // ... existant ...
    
    Route::middleware('role:admin')->group(function () {
        Route::get('config/marketing', [BoutiqueConfigController::class, 'marketing'])->name('config.marketing');
        Route::post('config/marketing/facebook', [BoutiqueConfigController::class, 'saveFacebook'])->name('config.facebook.save');
        Route::delete('config/marketing/facebook', [BoutiqueConfigController::class, 'disconnectFacebook'])->name('config.facebook.disconnect');
    });
});
```

### 5.2 Vue : `resources/views/dashboard/boutique/config-marketing.blade.php`

```
┌─────────────────────────────────────────────────────────┐
│  ⚡ Marketing                                           │
│                                                         │
│  ┌─── Meta (Facebook & Instagram) ──────────────────┐  │
│  │                                                    │  │
│  │  [Logo Meta]    Statut : 🔴 Non connecté          │  │
│  │                                                    │  │
│  │  ┌─────────────────────────────────────────────┐  │  │
│  │  │  Pixel ID                                   │  │  │
│  │  │  [________________________]                 │  │  │
│  │  │                                             │  │  │
│  │  │  Access Token (Conversions API)             │  │  │
│  │  │  [________________________]                 │  │  │
│  │  │                                             │  │  │
│  │  │  Code de test (optionnel)                   │  │  │
│  │  │  [________________________]                 │  │  │
│  │  │                                             │  │  │
│  │  │  [💾 Enregistrer]  [🔌 Déconnecter]        │  │  │
│  │  └─────────────────────────────────────────────┘  │  │
│  │                                                    │  │
│  │  💡 Où trouver ces informations ?                  │  │
│  │  1. Allez dans Meta Business Suite                 │  │
│  │  2. Paramètres > Sources de données > Pixels       │  │
│  │  3. Créez ou sélectionnez votre Pixel              │  │
│  │  4. Copiez l'ID et générez un token d'accès       │  │
│  │                                                    │  │
│  └────────────────────────────────────────────────────┘  │
│                                                         │
│  ┌─── Google Analytics ─────────────────────────────┐  │
│  │  (Prochainement)                                  │  │
│  └───────────────────────────────────────────────────┘  │
│                                                         │
│  ┌─── TikTok Pixel ──────────────────────────────────┐  │
│  │  (Prochainement)                                  │  │
│  └───────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
```

### 5.3 Sidebar : ajout du lien

Dans le menu "Boutique en ligne" du dashboard :

```
Boutique en ligne
├── Configuration
├── Marketing       ← nouveau
└── Commandes
```

---

## 6. Dashboard statistique

### 6.1 Vue : `resources/views/dashboard/boutique/marketing-stats.blade.php`

Affiche les événements envoyés aujourd'hui (stockés dans une table `facebook_events_log`) :

```
┌─────────────────────────────────────────────────────────┐
│  📊 Pixel Meta — Aujourd'hui                            │
│                                                         │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐  │
│  │ PageView │ │ Produits │ │ Ajouts   │ │ Paiements│  │
│  │   850    │ │ vus      │ │ panier   │ │ initiés  │  │
│  │          │ │   420    │ │   61     │ │   18     │  │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘  │
│                                                         │
│  ┌──────────┐ ┌──────────────────────────────────────┐  │
│  │ Achats   │ │ 💰 Chiffre d'affaires                 │  │
│  │   11     │ │    485 000 FCFA                       │  │
│  └──────────┘ └──────────────────────────────────────┘  │
│                                                         │
│  🟢 Pixel connecté : ✅  │  Dernier événement : 14:32  │
└─────────────────────────────────────────────────────────┘
```

### 6.2 Migration : `create_facebook_events_log_table`

```php
Schema::create('facebook_events_log', function (Blueprint $table) {
    $table->id();
    $table->uuid('institut_id');
    $table->string('event_name');
    $table->string('source');        // 'browser' ou 'server'
    $table->json('payload')->nullable();
    $table->boolean('success')->default(true);
    $table->timestamps();
    
    $table->index(['institut_id', 'created_at']);
});
```

Les statistiques sont calculées par des requêtes simples :

```php
// Dans BoutiqueConfigController
public function marketing()
{
    $stats = FacebookEventsLog::where('institut_id', $this->institutId())
        ->whereDate('created_at', today())
        ->selectRaw('event_name, count(*) as total')
        ->groupBy('event_name')
        ->pluck('total', 'event_name');
    
    $caAujourdhui = Commande::where('institut_id', $this->institutId())
        ->whereDate('created_at', today())
        ->sum('total');

    return view('dashboard.boutique.marketing-stats', compact('stats', 'caAujourdhui'));
}
```

---

## 7. Parcours utilisateur

### 7.1 Première connexion

```
1. L'utilisateur va dans Paramètres > Boutique > Marketing
2. Il clique sur "Configurer Meta"
3. Suit le guide étape par étape pour :
   a. Créer un Pixel dans Meta Business Suite
   b. Copier le Pixel ID
   c. Générer un Access Token
4. Colle les valeurs dans Maëlya Gestion
5. Clique "Enregistrer"
6. Le Pixel est actif immédiatement
```

### 7.2 Test

```
1. L'utilisateur saisit un "Code de test" (optionnel)
2. Visite sa boutique publique
3. Dans l'onglet Marketing de Maëlya, il voit les événements apparaître
4. Dans Meta Events Manager > Test Events, il voit aussi les événements
```

### 7.3 Campagne publicitaire

```
1. L'utilisateur crée une publicité dans Meta Ads Manager
2. Il choisit son Pixel comme source de suivi
3. La pub est diffusée → les clics arrivent sur la boutique
4. Les achats sont tracés automatiquement par le Pixel + CAPI
5. Dans Meta Ads Manager : ROAS visible, audiences de reciblage disponibles
```

---

## 8. Sécurité & conformité

### 8.1 Protection des données

| Donnée | Protection |
|---|---|
| `facebook_access_token` | Chiffré en base (`encrypted` cast Laravel) |
| Emails / Téléphones (CAPI) | Hashés SHA256 avant envoi |
| `event_id` | UUID unique pour déduplication |
| Logs | Pas de token en clair dans les logs |

### 8.2 Consentement RGPD

Ajouter une bannière cookies sur la boutique publique (obligation légale Côte d'Ivoire — loi 2013-450) :

```blade
{{-- Dans le layout public de la boutique --}}
@if(!request()->cookie('cookie_consent'))
<div class="cookie-banner">
    <p>Ce site utilise des cookies pour améliorer votre expérience, 
       analyser le trafic et personnaliser les publicités.</p>
    <button onclick="acceptCookies()">Accepter</button>
    <button onclick="rejectCookies()">Refuser</button>
</div>
@endif
```

Le Pixel ne se charge que si le consentement est donné :

```javascript
if (getCookie('cookie_consent') === 'accepted') {
    // Charger le Pixel
}
```

---

## 9. Plan de déploiement

### Phase 1 — MVP (2-3 jours)

| Tâche | Fichier(s) |
|---|---|
| Migration `boutique_configs` | `database/migrations/` |
| Page Marketing dans dashboard | `resources/views/dashboard/boutique/config-marketing.blade.php` |
| Script Pixel dans le `<head>` public | `resources/views/boutique/layouts/public.blade.php` |
| Événements navigateur (`ViewContent`, `AddToCart`, `Purchase`) | Vues boutique |
| Service `FacebookCapiService` | `app/Services/FacebookCapiService.php` |
| Job `SendFacebookPurchaseEvent` | `app/Jobs/SendFacebookPurchaseEvent.php` |
| Logs événements | `database/migrations/` + modèle |

### Phase 2 — Dashboard stats (1-2 jours)

| Tâche |
|---|
| Page stats `marketing-stats.blade.php` |
| Requêtes agrégées par événement |
| Affichage CA attribué |

### Phase 3 — OAuth + automatisation (futur)

| Tâche |
|---|
| App Facebook vérifiée (Business Verification) |
| OAuth "Connecter Facebook" |
| Récupération automatique Pixel ID + Token |
| Renouvellement automatique du token |

---

## 10. Checklist pré-lancement

- [ ] Migration exécutée sans erreur
- [ ] `facebook_access_token` bien chiffré en base
- [ ] Pixel se charge uniquement si configuré
- [ ] Événements navigateur testés : `PageView`, `ViewContent`, `AddToCart`, `Purchase`
- [ ] CAPI testée avec Test Code Meta
- [ ] Déduplication `event_id` vérifiée
- [ ] Consentement cookies fonctionnel
- [ ] Aucun token en clair dans les logs
- [ ] Mobile : Pixel chargé correctement
- [ ] Dark mode : page Marketing lisible
- [ ] Route protégée par `role:admin`
- [ ] Bouton Déconnecter efface bien les champs
