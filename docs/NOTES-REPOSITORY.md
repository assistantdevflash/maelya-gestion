# 📝 Notes Importantes du Repository

**Source :** Fichiers mémoire du repository (`/memories/repo/`)  
**Date :** Juillet 2026

---

## 🚀 Commande de Déploiement (LWS)

**TOUJOURS utiliser cette commande exacte :**

```bash
cd ~/maelya && git pull origin main && php artisan route:clear && php artisan view:clear && php artisan config:cache && php artisan route:cache
```

**Si nouvelle migration :**
```bash
php artisan migrate --force
```

**Règle importante :**
- Toujours terminer chaque tâche avec cette commande
- Ne jamais utiliser de chemin absolu pour `php artisan` (cPanel gère le PATH)
- Ne jamais omettre, même pour de petites modifications

---

## 🎨 Rebuild CSS Tailwind

**TOUJOURS exécuter après modification de :**
- Fichiers Blade (nouvelles classes Tailwind)
- `resources/css/app.css`

```bash
cd "/Volumes/Dev Disk/maelya-gestion/app"
npm run build
```

---

## 🧩 Blade Tips et Pièges

### 1. Échappement @ dans JSON-LD
```blade
{{-- ❌ INTERDIT : Blade interprétera comme directives --}}
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Product"
}
</script>

{{-- ✅ CORRECT : Échapper avec @@ --}}
<script type="application/ld+json">
{
  "@@context": "https://schema.org",
  "@@type": "Product"
}
</script>
```

### 2. Composants Anonymes Blade
**Piège :** Les composants anonymes `<x-component>` n'héritent PAS des variables du parent.

```blade
{{-- ❌ NE FONCTIONNE PAS --}}
{{-- Dans parent.blade.php --}}
@php $title = "Mon Titre"; @endphp
<x-card></x-card>  {{-- $title n'existe pas ici ! --}}

{{-- ✅ FONCTIONNE --}}
<x-card :title="$title"></x-card>

{{-- Dans components/card.blade.php --}}
@props(['title'])
<div>{{ $title }}</div>
```

### 3. Architecture Layout
```
<x-landing-layout>  (usage dans vue)
    ↓
components/landing-layout.blade.php  (@props + @include)
    ↓
layouts/landing.blade.php  (HTML structure)
```

---

## 📋 Plan de Développement (Priorités 1-2-3)

### ✅ Fonctionnalités Implémentées (14/14 - 100%)

#### Priorité 1 — Impact métier
1. ✅ Pourboires (commit 8f41109)
2. ✅ Brouillons de panier (commit 0346056)
3. ✅ Facture FAC-YYYY-NNNN + PDF (commit accc100)
4. ✅ Avoirs → code réduction (commit c948957)
5. ✅ Codes-barres produits backend (commit 4c10b6c)
6. ✅ Rapport CA par catégorie (commit d8c8340)

#### Priorité 2 — Rétention
7. ✅ Segmentation client (filtres) (commit 0b4c8eb)
8. ✅ Anniversaire J-7 (command) (commit 7873ed4)
9. ✅ AvisClient (sondage + modération) (commit 0271a06)
10. ✅ Carte fidélité QR publique (commit bbbcdf3)
11. ✅ Timeline fiche client (commit b7f1e9e)

#### Priorité 3 — Finances
12. ✅ Trésorerie prévisionnelle (commit 4da8a3d)
13. ✅ Détection anomalies (commit 03aef32)

---

## ⚠️ Pièges Rencontrés et Solutions

### 1. Migration ENUM MySQL vs SQLite
**Problème :** ENUM non supporté par SQLite (tests).

**Solution :**
```php
public function up()
{
    if (DB::getDriverName() !== 'mysql') {
        return; // Skip pour SQLite
    }
    
    // ALTER TABLE ... MODIFY COLUMN ... ENUM(...)
}

public function down()
{
    if (DB::getDriverName() !== 'mysql') {
        return;
    }
    // ...
}
```

### 2. Prestation::create() sans categorie_id
**Problème :** `categorie_id` NOT NULL, crash si omis.

**Solution :**
```php
// Dans tests, toujours créer catégorie d'abord
$categorie = CategoriePrestation::factory()->create();
$prestation = Prestation::create([
    'categorie_id' => $categorie->id,
    // ...
]);
```

### 3. BelongsToInstitut Global Scope
**Problème :** Scope auto filtre par `institut_id`, impossible de tester cross-institut.

**Solution :**
```php
// Dans tests cross-institut
$produits = Produit::withoutGlobalScopes()->get();
```

### 4. Route Model Binding + Scope Institut
**Problème :** Accès non autorisé retourne 404 (pas 403).

**Solution :**
```php
// Dans tests
$response->assertNotFound(); // Pas assertForbidden()
```

### 5. Helpers Tests
**Disponibles dans `tests/TestCase.php` :**
```php
$admin = $this->creerAdmin();
$institut = $this->creerInstitut();
$plan = $this->creerPlan();
```

---

## 🧪 Alpine.js + Livewire : Patterns Spécifiques

### 1. Alpine x-data sur Root Livewire
```blade
<div x-data="caisseApp()" wire:id="...">
    {{-- $wire.clientId accessible --}}
    <button @click="$wire.addProduit(id)">Add</button>
</div>
```

### 2. Structure Panier Alpine
```javascript
// Panier = object indexé par clé composite
panier: {
  'produit_uuid-123': {
    type: 'produit',
    id: 'uuid-123',
    nom: 'Shampoing',
    prix: 5000,
    quantite: 2
  },
  'prestation_uuid-456': {
    type: 'prestation',
    id: 'uuid-456',
    nom: 'Coupe',
    prix: 3000,
    quantite: 1
  }
}
```

### 3. Passer Options à Alpine Component depuis Blade
```blade
<div x-data="caisseApp(@js([
    'instituts' => $instituts,
    'currentUser' => auth()->user(),
    'config' => $config
]))">
```

---

## 🗂️ Structures Importantes

### Routes Console (Laravel 11+ Style)
```php
// routes/console.php
use Illuminate\Support\Facades\Schedule;

Schedule::command('clients:rappel-anniversaire')->daily();
Schedule::command('maelya:anomalies')->dailyAt('09:00');
```

### Traits et Services
```php
// Auditable trait (ajouter sur modèles critiques)
use App\Traits\Auditable;

// Notifications in-app
use App\Models\Notif;

// Push notifications
use App\Services\PushNotificationService;
PushNotificationService::sendToUser($user, $titre, $corps, $url);
```

### Conventions Design
```html
<!-- Card -->
<div class="card">
    <div class="card-header">...</div>
    <div class="card-body">...</div>
</div>

<!-- Boutons -->
<button class="btn-primary">Action</button>
<button class="btn-outline">Annuler</button>

<!-- Form -->
<input type="text" class="form-input">
<select class="form-select">...</select>

<!-- Badge -->
<span class="badge badge-success">Actif</span>
<span class="badge badge-warning">En attente</span>
```

### Dark Mode
**Toujours ajouter classes dark: :**
```html
<div class="bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-200">
```

---

## 📊 Modèles de Données Importants

### Vente
```php
// Relations
hasMany VenteItem (produits + prestations)
belongsTo Client
belongsTo Institut
belongsTo User (employé qui a vendu)

// Champs
numero_facture (nullable, généré sur demande)
montant_pourboire (ajouté récemment)
type_paiement (espece, carte, mobile_money, credit)
```

### VenteItem
```php
// Polymorphique
itemable_type ('App\Models\Produit' | 'App\Models\Prestation' | null)
itemable_id (UUID ou null)
libre_type (ENUM: 'produit', 'prestation', null)
libre_titre (si item libre)
```

### Avoir
```php
// Relations
belongsTo Vente (vente d'origine)
hasOne CodeReduction (généré automatiquement)

// Workflow
1. Créer Avoir
2. Généré automatiquement CodeReduction
3. Client utilise code à prochaine vente
```

### AvisClient
```php
// Champs
token (unique, public URL)
note (1-5)
commentaire (nullable)
modere (boolean, gérant valide avant publication)
```

### Commande (Boutique)
```php
// Numéro unique GLOBAL (pas par institut)
numero (CMD-YYYYMMDD-XXXX)

// Relations
hasMany CommandeItem
belongsTo Client
belongsTo Institut
```

---

## 🔒 Sécurité et Bonnes Pratiques

### Tests
**1 test Feature minimum par fonctionnalité critique :**
```php
// tests/Feature/CaisseTest.php
public function test_pourboire_ajoute_au_montant_total()
{
    $vente = Vente::create([...]);
    $this->assertEquals($vente->montant_total, $vente->montant + $vente->montant_pourboire);
}
```

### Commits
**1 commit propre par item, push à la fin de chaque :**
```bash
git commit -m "✨ Feature: Pourboires caisse"
git commit -m "🐛 Fix: Calcul total avec pourboire"
git commit -m "📝 Docs: Update README pourboires"
```

### Notifications
**Pattern standard :**
```php
// Notification complète
NotificationService::notifyUser($user, $titre, $message, $url);
// → Crée Notif in-app
// → Envoie push notification
// → Envoie email si configuré

// Mail dédié pour cas importants
Mail::to($user)->send(new AbonnementExpire($abonnement));
```

---

## 🎯 Workflow Standard

### Pour chaque nouvelle fonctionnalité
```
1. Créer migration(s) si nécessaire
2. Créer/modifier modèle(s)
3. Créer contrôleur + routes
4. Créer vue(s) Blade
5. npm run build (si nouvelles classes Tailwind)
6. Tester en local
7. Créer test Feature
8. Commit propre
9. Push
10. Déployer LWS avec commande standard
```

### Checklist Qualité
```
[ ] Design suit conventions (card, btn-primary, etc.)
[ ] Dark mode ajouté (dark:bg-*, dark:text-*)
[ ] Notifications implémentées (in-app + push + mail si pertinent)
[ ] Test Feature créé
[ ] npm run build exécuté
[ ] Commit descriptif (emoji + message)
[ ] Commande déploiement rappelée
```

---

## 📈 Métriques Fonctionnalités

**Fonctionnalités complètes (Juillet 2026) :**
- ✅ 14/14 priorités implémentées (100%)
- ✅ ~50 commits pour plan priorités
- ✅ Tests ajoutés pour fonctionnalités critiques
- ✅ Documentation intégrée (docblocks, README)

**Technologies maîtrisées :**
- Laravel 12 (migrations ENUM avec fallback)
- Livewire 3 (composants réactifs)
- Alpine.js 3 (UI interactions)
- Tailwind CSS (dark mode)
- Web Push API (notifications)
- QR Codes (carte fidélité)
- PDF Generation (DomPDF)
- Excel Export (Maatwebsite)

---

**✅ Ces notes sont essentielles pour comprendre les spécificités du projet !**
