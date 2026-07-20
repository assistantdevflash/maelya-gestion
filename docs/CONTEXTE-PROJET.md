# 📋 Contexte Complet du Projet Maëlya Gestion

**Date de dernière mise à jour :** 17 juillet 2026  
**Version Laravel :** 12.56.0  
**Environnement :** Dev local + Production LWS

---

## 🎯 Vue d'ensemble

**Maëlya Gestion** est une application SaaS de gestion complète pour salons de beauté et instituts en Côte d'Ivoire (devise : FCFA). Elle inclut :

- 💼 Gestion multi-établissements (instituts)
- 👥 Gestion clients avec historique
- 📅 Système de rendez-vous
- 💰 Caisse (ventes produits + prestations)
- 📦 Gestion stocks et produits
- 🛒 **Boutique en ligne** (commandes avec livraison)
- 💳 Système d'abonnements et plans tarifaires
- 🎁 Fidélité clients (points/crédits)
- 📊 Statistiques et rapports
- 🔔 Notifications push (Web Push API)
- 📱 PWA (installable sur mobile/desktop)

---

## 🏗️ Architecture Technique

### Stack
- **Backend :** Laravel 12.56.0 (PHP 8.3+)
- **Frontend :** Blade + Alpine.js 3.x + Livewire 3.x
- **CSS :** Tailwind CSS 3.x
- **Build :** Vite 7.3.2
- **Base de données :** MySQL (c2780050c_maelya_db en prod)
- **Cache :** Redis/File
- **Queue :** Database
- **Email :** SMTP (Mailtrap dev / production SMTP)
- **Storage :** Local/Public (LWS en prod)

### Dépendances principales
```json
{
  "php": "^8.3",
  "laravel/framework": "^12.0",
  "livewire/livewire": "^3.0",
  "barryvdh/laravel-dompdf": "^3.0",
  "maatwebsite/excel": "^3.1",
  "minishlink/web-push": "^9.0"
}
```

### URLs
- **Dev local :** http://127.0.0.1:8000
- **Production :** https://maelyagestion.com
- **Vite dev :** http://localhost:5173

---

## 📂 Structure des Dossiers Clés

```
app/
├── app/
│   ├── Http/Controllers/
│   │   ├── Dashboard/           # Contrôleurs du dashboard
│   │   │   ├── ProduitController.php
│   │   │   ├── ProduitImageController.php (AJAX gallery)
│   │   │   └── ...
│   │   ├── BoutiqueController.php   # Boutique publique
│   │   └── Auth/
│   ├── Livewire/
│   │   └── Caisse.php           # Caisse en temps réel
│   ├── Models/
│   │   ├── User.php
│   │   ├── Institut.php
│   │   ├── Produit.php
│   │   ├── Commande.php         # IMPORTANT: numérotation globale
│   │   └── ...
│   ├── Mail/                    # Emails transactionnels
│   ├── Notifications/
│   └── Services/
│       └── PushNotificationService.php
│
├── resources/
│   ├── views/
│   │   ├── dashboard/           # Interface admin/gérant
│   │   │   ├── produits/
│   │   │   │   ├── index.blade.php
│   │   │   │   ├── form.blade.php  # CREATE + EDIT
│   │   │   │   └── edit.blade.php  # Wrapper
│   │   │   └── ...
│   │   ├── boutique/
│   │   │   ├── index.blade.php  # Liste produits (Alpine.js)
│   │   │   └── produit.blade.php
│   │   ├── livewire/
│   │   │   └── caisse.blade.php
│   │   └── layouts/
│   │       └── dashboard.blade.php
│   ├── css/app.css
│   └── js/app.js
│
├── routes/
│   ├── web.php                  # Routes principales
│   ├── api.php
│   ├── auth.php
│   └── console.php
│
├── public/
│   ├── icons/                   # PWA icons (PNG + maskable)
│   │   ├── icon-192.png?v=4    # Nouveau logo
│   │   ├── badge-72.png        # Notification badge (blanc)
│   │   └── ...
│   ├── sw.js                    # Service Worker v5
│   ├── manifest.json
│   └── storage -> ../storage/app/public
│
├── database/
│   ├── migrations/
│   └── seeders/
│
└── storage/
    └── app/public/              # Uploads (produits, logos, etc.)
```

---

## 🔑 Conventions de Code

### Nommage
- **Routes :** `dashboard.produits.index`, `shop.commander`
- **Contrôleurs :** PascalCase, suffix `Controller`
- **Modèles :** Singular PascalCase (`Produit`, `Commande`)
- **Tables :** Plural snake_case (`produits`, `commandes`)
- **Vues :** kebab-case (`produits/form.blade.php`)
- **Livewire :** PascalCase (`Caisse.php`)

### UUID
**IMPORTANT :** Tous les IDs sont des UUIDs v7 (pas d'auto-increment).
```php
use Illuminate\Database\Eloquent\Concerns\HasUuids;

protected $keyType = 'string';
public $incrementing = false;
```

### Relations
```php
// belongsTo
public function institut(): BelongsTo

// hasMany
public function produits(): HasMany

// belongsToMany
public function prestations(): BelongsToMany
```

### Cache
```php
Cache::remember('boutique_' . $institut->id . '_produits', 3600, fn() => ...);
Cache::forget('boutique_' . $institut->id . '_produits');
```

**Clés de cache à vider ensemble :**
- `boutique_{institut_id}_produits`
- `caisse_catalog_{institut_id}`

---

## 🚨 Points Critiques et Décisions Architecturales

### 1. Numérotation des Commandes
**PROBLÈME RÉSOLU :** La contrainte `UNIQUE` sur `commandes.numero` est **globale** (tous instituts), mais le générateur cherchait par `institut_id` → doublons.

**Solution :** `Commande::genererNumero()` recherche maintenant **globalement** avec `lockForUpdate()`.

```php
// app/Models/Commande.php
public static function genererNumero(string $institutId): string
{
    $lastCommande = self::where('numero', 'like', "CMD-$date-%")
        ->lockForUpdate()
        ->orderByRaw('CAST(SUBSTRING_INDEX(numero, \'-\', -1) AS UNSIGNED) DESC')
        ->first();
    // ...
}
```

### 2. Galerie Photos Produits (AJAX)
**Architecture :** Séparée du formulaire principal pour éviter imbrication HTML invalide.

**Fichiers clés :**
- `ProduitImageController.php` : toutes méthodes retournent JSON
- `form.blade.php` : composant Alpine.js `galerieManager()`
- Upload max : **5 Mo** par image, **5 images max** par produit

**Bug résolu :** `@json()` avec `(bool)` cast + `fn()` confondait le parser Blade → utiliser `@php` pour pré-calcul.

```php
@php
    $imagesData = ($images ?? collect())->map(function($img) {
        return [
            'id' => $img->id,
            'url' => asset('storage/' . $img->chemin),
            'is_principale' => $img->is_principale ? true : false,
        ];
    })->values()->toArray();
@endphp
<div x-data="galerieManager({ images: {{ json_encode($imagesData) }}, ... })">
```

### 3. Formulaires Imbriqués (DANGER !)
**BUG CRITIQUE CORRIGÉ :** Ne JAMAIS imbriquer `<form>` dans `<form>`.

```html
<!-- ❌ INTERDIT -->
<form id="produit-form">
    <form id="delete-form"></form>
</form>

<!-- ✅ CORRECT -->
<form id="produit-form">
    ...
</form>
<form id="delete-form" style="display:none">
    ...
</form>
```

### 4. Boutique : Modal Commander
**BUG RÉSOLU :** `:disabled="submitting"` sur `<button type="submit">` annulait la soumission.

**Solution :** Pas de `disabled`, uniquement flag `submitting` pour UI + `event.preventDefault()` dans handler.

```javascript
handleSubmit(event) {
    if (this.submitting) { event.preventDefault(); return; }
    this.submitting = true;
    // Le formulaire se soumet normalement
}
```

**Réouverture automatique du modal si erreur serveur :**
```php
commandeOpen: {{ session('error') || $errors->any() ? 'true' : 'false' }},
```

### 5. Service Worker et Cache
**Version actuelle :** `maelya-v5`

**Assets versionés :** `?v=4` pour bypass cache (icons, manifest)

**Fichiers :**
- `public/sw.js` : gestion offline + push notifications
- Cache strategy : `cacheFirst` pour assets statiques

### 6. Notifications Push
**Service :** `PushNotificationService.php`

**Icônes :**
- `icon-192.png?v=4` : logo principal
- `badge-72.png?v=4` : silhouette blanche (Android status bar)

**Méthodes :**
```php
PushNotificationService::sendToUser($user, $titre, $corps, $url);
PushNotificationService::sendToAdmins($titre, $corps, $url);
```

### 7. Blade vs Alpine.js
**Règle :** Pré-calculer les données complexes dans `@php` avant de passer à Alpine.

```php
// ❌ Ne fonctionne pas bien
@json(($data)->map(fn($x) => ['val' => (bool)$x->field]))

// ✅ Fonctionne
@php $prepared = $data->map(fn($x) => ['val' => $x->field ? true : false])->toArray(); @endphp
{{ json_encode($prepared) }}
```

---

## 🐛 Bugs Récemment Corrigés (Juillet 2026)

| Date | Bug | Solution | Commit |
|------|-----|----------|--------|
| 13/07 | Produits page non centrée | Ajout `mx-auto` | 7fdb1da |
| 13/07 | Toggles CSS invisibles | Remplacement par Alpine.js | 7fdb1da |
| 13/07 | Upload galerie imbriqué | Formulaire sorti du form principal | 7fdb1da |
| 13/07 | Logo push ancien | Icons versionés ?v=4 + SW v5 | 4b161ed |
| 13/07 | Parse error commentaire PHP | Bloc `/** */` réparé | 407c324 |
| 13/07 | Méthodes dupliquées | Suppression doublons store/destroy | cc23495 |
| 13/07 | Images galerie vides | Pré-calcul @php au lieu de @json | 4821a5b |
| 13/07 | Bouton Catégories x2 | Doublon supprimé | a2335c4 |
| 13/07 | Boutique cache non vidé | Cache::forget ajouté | 07ed291 |
| 13/07 | Bouton Enregistrer = suppression | delete-form déplacé hors form | 4d9f16c |
| 13/07 | Modal commande silencieuse | Réouverture + erreur inline | e947880 |
| 13/07 | Bouton disabled bloque submit | Retrait :disabled | e67874a |
| 13/07 | Doublon CMD-YYYYMMDD-XXXX | Numérotation globale + lock | 1f70a2e |

---

## 🔧 Configuration Environnement

### .env (Dev Local)
```env
APP_NAME="Maëlya Gestion"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=maelya_dev
DB_USERNAME=root
DB_PASSWORD=

VITE_DEV_SERVER_URL=http://localhost:5173
```

### .env (Production LWS)
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://maelyagestion.com

DB_DATABASE=c2780050c_maelya_db
```

### Commandes Utiles

**Démarrage local :**
```bash
cd "/Volumes/Dev Disk/maelya-gestion/app"
php artisan serve              # Terminal 1
npm run dev                    # Terminal 2
```

**Tests avant push :**
```bash
php -l path/to/file.php        # Vérifier syntaxe
php artisan view:clear         # Vider cache Blade
php artisan config:clear       # Vider config
```

**Déploiement LWS :**
```bash
git push origin main

# Puis sur le serveur LWS :
cd ~/maelya && git pull origin main && php artisan view:clear && php artisan cache:clear

# Si nouvelle migration :
php artisan migrate --force
```

---

## 📦 Modules et Fonctionnalités

### Système d'Abonnements
**Plans disponibles :**
- `essai` : Gratuit 30j, boutique offerte
- `starter` : 5000 FCFA/mois
- `pro` : 15000 FCFA/mois
- `premium` : 50000 FCFA/mois

**Vérification accès boutique :**
```php
$user->hasBoutiqueAccess() // true pour plan essai + plans avec option boutique
```

### Boutique en Ligne
**Architecture :**
- Catalogue public : `/shop/{slug}`
- Panier localStorage + Alpine.js
- Commande : formulaire AJAX avec modal
- Paiement : Cash à la livraison uniquement
- Notifications : Email + Push au gérant

**Contraintes :**
- Stock > 0 requis
- `visible_boutique = true`
- `actif = true`
- `hasBoutiqueAccess()` validé

### Caisse (Livewire)
**Composant :** `app/Livewire/Caisse.php`

**Features :**
- Recherche produits/prestations temps réel
- Panier réactif
- Application crédits/codes promo
- Multi modes paiement
- Historique ventes

### Gestion Stocks
**Mouvements automatiques :**
- Vente → décrémente stock
- Retour → incrémente stock
- Correction manuelle → historique mouvement

---

## 🎨 Design System

### Couleurs (Tailwind)
- **Primary :** Purple 600 (`bg-primary-600`, `text-primary-600`)
- **Success :** Emerald
- **Warning :** Amber
- **Danger :** Red

### Composants Réutilisables
```blade
{{-- Boutons --}}
<button class="btn-primary">...</button>
<button class="btn-outline">...</button>

{{-- Cards --}}
<div class="card">
    <div class="card-header">...</div>
    <div class="card-body">...</div>
</div>

{{-- Inputs --}}
<input type="text" class="form-input">
<select class="form-select">...</select>
```

### Dark Mode
Supporté via classes Tailwind `dark:` — basé sur la préférence système.

---

## 📱 PWA

**Fichiers :**
- `public/manifest.json` : config PWA
- `public/sw.js` : Service Worker
- Icons : `/public/icons/` (192, 512, maskable)

**Installation :**
- Chrome/Edge : bouton "Installer" automatique
- iOS : "Ajouter à l'écran d'accueil"

**Offline :**
- Page `/offline.html` servie si hors ligne
- Cache assets statiques (stratégie cacheFirst)

---

## 🔐 Authentification et Rôles

**Rôles :**
- `super_admin` : Accès complet système
- `proprietaire` : Propriétaire d'établissement(s)
- `gerant` : Gérant d'un institut
- `employe` : Employé d'un institut

**Middleware :**
```php
Route::middleware(['auth', 'role:proprietaire'])->group(function() {
    // Routes réservées propriétaires
});
```

**Relations :**
- User → owns many Instituts (proprietaire_id)
- User → works at Institut (via employes table)

---

## 📊 Base de Données

### Tables Principales
```
users
instituts
clients
produits
  └── produit_images (galerie)
prestations
commandes
  └── commande_items
ventes
  └── vente_items
rdvs (rendez-vous)
abonnements
push_subscriptions
```

### Migrations Importantes
- `visible_boutique` sur `produits` : défaut `true`
- `numero` sur `commandes` : UNIQUE global (pas par institut !)
- UUIDs v7 partout (pas d'auto-increment)

---

## 🧪 Tests et Validation

**Avant chaque push :**
1. Vérifier syntaxe : `php -l file.php`
2. Tester fonctionnalité en local
3. Vérifier logs : `storage/logs/laravel.log`
4. Clear caches : `php artisan view:clear && php artisan config:clear`

**En cas d'erreur 500 en prod :**
```bash
# SSH sur LWS
tail -100 ~/maelya/storage/logs/laravel.log
```

---

## 📝 Notes pour le Prochain Agent

### Préférences Utilisateur (depuis /memories/)
- Toujours répondre en **français**
- Projet déployé sur **LWS** (pas Vercel/Railway)
- Commande de déploiement complète obligatoire après chaque tâche
- Ne pas rappeler commandes si explicitement en dev local

### Patterns à Suivre
1. **Jamais d'auto-increment**, toujours UUIDs
2. **Cache double** : boutique + caisse (les 2 ensemble)
3. **Validation serveur + client** pour formulaires critiques
4. **JSON responses** pour AJAX (pas de redirect)
5. **Anti double-clic** sur tous les formulaires de paiement/commande
6. **lockForUpdate()** pour numérotations séquentielles
7. **Pré-calcul @php** avant Alpine.js pour data complexes

### Erreurs Fréquentes à Éviter
❌ `@json()` avec cast `(bool)` et `fn()` → parser Blade confus  
❌ Formulaires imbriqués `<form><form>` → HTML invalide  
❌ `:disabled` sur `<button type="submit">` → bloque soumission  
❌ Filter par `institut_id` si contrainte UNIQUE globale  
❌ Oublier de vider cache boutique ET caisse ensemble  

### Workflow Git
```bash
# Faire modifications
php -l fichier.php              # Test syntaxe
git add -A
git commit -m "🐛/✨/📝 Message"
git push origin main

# Ne jamais push sans test local !
```

### Structure des Commits
- `🐛 Fix:` Bug correction
- `✨ Feature:` Nouvelle fonctionnalité
- `📝 Docs:` Documentation
- `🎨 Style:` UI/UX
- `♻️ Refactor:` Code cleanup
- `⚡ Perf:` Performance

---

## 🚀 Checklist Démarrage Nouvel Ordinateur

```bash
# 1. Clone du repo
git clone [URL] maelya-gestion
cd maelya-gestion/app

# 2. Dépendances
composer install
npm install

# 3. Environnement
cp .env.example .env
php artisan key:generate

# 4. Base de données
# Créer DB locale
php artisan migrate
php artisan db:seed  # optionnel

# 5. Storage link
php artisan storage:link

# 6. Lancer serveurs
php artisan serve    # Terminal 1
npm run dev          # Terminal 2

# 7. Accès
# http://127.0.0.1:8000
```

---

## 📞 Support et Ressources

**Documentation Laravel :** https://laravel.com/docs/12.x  
**Alpine.js :** https://alpinejs.dev  
**Tailwind CSS :** https://tailwindcss.com  
**Livewire :** https://livewire.laravel.com

**Logs Production :**
```bash
ssh user@lws-server
tail -f ~/maelya/storage/logs/laravel.log
```

---

## ✅ État Actuel (17 Juillet 2026)

**Fonctionnalités Stables :**
- ✅ Multi-instituts
- ✅ Gestion clients/RDV
- ✅ Caisse Livewire complète
- ✅ Boutique en ligne + commandes
- ✅ Abonnements SaaS
- ✅ Notifications push
- ✅ PWA installable
- ✅ Galerie produits AJAX
- ✅ Système de crédits/fidélité

**En Cours / À Faire :**
- ⏳ Tests unitaires
- ⏳ Migration PostgreSQL (optionnel)
- ⏳ API mobile (si nécessaire)
- ⏳ Internationalisation (actuellement FR uniquement)

**Bugs Connus :**
- Aucun critique à ce jour

---

**🎯 Prêt à continuer le développement ! Bon courage ! 🚀**
