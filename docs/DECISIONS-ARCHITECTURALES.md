# 🧠 Décisions Architecturales et Leçons Apprises

**Projet :** Maëlya Gestion  
**Date :** Juillet 2026  
**Contexte :** Documentation des choix techniques et erreurs évitées

---

## 🏛️ Décisions Architecturales Majeures

### 1. UUIDs v7 au lieu d'Auto-Increment

**Décision :** Tous les IDs primaires sont des UUIDs v7, pas d'auto-increment.

**Pourquoi :**
- ✅ Génération côté application (pas de round-trip DB)
- ✅ Pas de collision multi-instituts
- ✅ Sécurité : IDs non-séquentiels (pas de guessing)
- ✅ Scalabilité : merge de données entre serveurs simplifié
- ✅ UUID v7 : chronologiquement triable (avantage sur v4)

**Implications :**
```php
// Dans tous les modèles
use Illuminate\Database\Eloquent\Concerns\HasUuids;

protected $keyType = 'string';
public $incrementing = false;

// Dans migrations
$table->uuid('id')->primary();
```

**⚠️ Piège évité :**
- Ne JAMAIS faire `$model->id++` ou opérations arithmétiques sur IDs
- Toujours utiliser `->latest()` au lieu de `->orderBy('id', 'desc')`

---

### 2. Multi-Tenancy par Institut (Soft)

**Décision :** Isolation par `institut_id` au niveau applicatif (pas de DB séparées).

**Pourquoi :**
- ✅ Simplicité déploiement (1 seule DB)
- ✅ Migrations centralisées
- ✅ Agrégations cross-institut possibles (stats globales)
- ✅ Coût hébergement réduit
- ❌ Risque de leak données si query mal scopée (acceptable avec discipline)

**Implementation :**
```php
// Global scope dans modèles
protected static function booted()
{
    static::addGlobalScope('institut', function (Builder $builder) {
        if (auth()->check() && !auth()->user()->isSuperAdmin()) {
            $builder->where('institut_id', auth()->user()->current_institut_id);
        }
    });
}
```

**⚠️ Exception Importante :**
Certains champs ont contrainte UNIQUE **globale** (tous instituts) :
- `commandes.numero` : CMD-YYYYMMDD-XXXX
- `users.email`

**LEÇON :** Toujours vérifier portée de contrainte UNIQUE avant d'ajouter filtre `institut_id` dans générateur.

---

### 3. Cache Multi-Niveaux

**Décision :** Cache séparé par ressource + institut avec invalidation explicite.

**Pattern :**
```php
// Lecture
Cache::remember("boutique_{$institut->id}_produits", 3600, function() {
    return Produit::where('institut_id', $institut->id)
        ->where('visible_boutique', true)
        ->get();
});

// Invalidation
Cache::forget("boutique_{$institut->id}_produits");
Cache::forget("caisse_catalog_{$institut->id}");
```

**Pourquoi clés multiples pour même donnée (boutique + caisse) :**
- ✅ Structures sérialisées différentes (boutique = avec images, caisse = light)
- ✅ TTL différents (boutique 1h, caisse 5min)
- ✅ Invalidation granulaire possible

**⚠️ Piège évité :**
Ne JAMAIS oublier d'invalider TOUS les caches affectés lors d'une mutation :
```php
// ❌ INCOMPLET
Cache::forget("boutique_{$institut->id}_produits");

// ✅ COMPLET
Cache::forget("boutique_{$institut->id}_produits");
Cache::forget("caisse_catalog_{$institut->id}");
```

---

### 4. Galerie Produits : AJAX au lieu de Form Classique

**Décision :** Upload images via AJAX fetch(), pas de soumission form synchrone.

**Pourquoi :**
- ✅ UX : feedback immédiat (spinner, success, erreur)
- ✅ Pas de rechargement page
- ✅ Upload progressif (image par image)
- ✅ Évite imbrication forms (HTML invalid)
- ✅ Gestion erreurs granulaire

**Architecture :**
```
┌─────────────────┐
│  form.blade.php │
│  (Alpine.js)    │
└────────┬────────┘
         │ fetch()
         ↓
┌─────────────────────────┐
│ ProduitImageController  │
│ (retourne JSON)         │
└────────┬────────────────┘
         │
         ↓
┌─────────────────┐
│ ProduitImage    │
│ Model + Storage │
└─────────────────┘
```

**Code pattern :**
```javascript
async function uploadImage(file) {
    const formData = new FormData();
    formData.append('photo', file);
    
    const response = await fetch(`/dashboard/produits/${produitId}/images`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: formData
    });
    
    const data = await response.json();
    // Mettre à jour UI avec data.image
}
```

**⚠️ LEÇON CRITIQUE :**
**JAMAIS imbriquer `<form>` dans `<form>` !**

```html
<!-- ❌ INTERDIT : HTML invalide, comportement imprévisible -->
<form id="produit-form" method="POST">
    <input name="titre">
    <form id="delete-form" method="POST">
        @method('DELETE')
    </form>
    <button type="submit">Save</button>  <!-- ⚠️ Peut trigger DELETE ! -->
</form>

<!-- ✅ CORRECT : Forms séparés -->
<form id="produit-form" method="POST">
    <input name="titre">
    <button type="submit">Save</button>
</form>

<form id="delete-form" method="POST" style="display:none">
    @method('DELETE')
</form>
```

**Ce bug a causé :** Bouton "Enregistrer" supprimait le produit au lieu de l'updater !

---

### 5. Boutique : Pas de :disabled sur Submit

**Décision :** Ne JAMAIS utiliser `:disabled="submitting"` sur bouton `type="submit"`.

**Pourquoi :**
```html
<!-- ❌ NE FONCTIONNE PAS -->
<button type="submit" :disabled="submitting">Submit</button>
<!-- Alpine.js rend disabled=true AVANT que le form ne soumette → soumission annulée ! -->

<!-- ✅ FONCTIONNE -->
<button type="submit" @click="handleSubmit">
    <span x-text="submitting ? 'Envoi...' : 'Submit'"></span>
</button>

<script>
handleSubmit(event) {
    if (this.submitting) {
        event.preventDefault();  // Empêcher double-clic
        return;
    }
    this.submitting = true;
    // Form se soumet normalement
}
</script>
```

**Pourquoi c'est subtil :**
Le navigateur évalue `disabled` AU MOMENT DU CLIC, et Alpine.js modifie le DOM PENDANT le handler de clic, AVANT que le form ne soumette.

**Solution :** Flag `submitting` pour l'UI, mais `event.preventDefault()` dans handler JS.

---

### 6. Blade @json() : Pièges du Parser

**Décision :** Pré-calculer données complexes dans `@php` avant de passer à Alpine.js.

**Pourquoi :**
```blade
<!-- ❌ FAIL : Parser Blade confus -->
@json($images->map(fn($i) => ['active' => (bool)$i->principale]))

<!-- ✅ WORKS -->
@php
    $imagesData = $images->map(function($i) {
        return ['active' => $i->principale ? true : false];
    })->toArray();
@endphp
<div x-data="{ images: {{ json_encode($imagesData) }} }">
```

**Explication :**
- Parser Blade cherche `@endphp`, `@endif`, etc.
- Arrow functions `fn()` + cast `(bool)` dans `@json()` confondent le tokenizer
- Symptôme : page blanche, aucune erreur claire

**Règle :** Si `@json()` contient plus qu'une simple variable, utiliser `@php` + `json_encode()`.

---

### 7. Service Worker : Versioning Agressif

**Décision :** Versionner TOUS les assets PWA (icons, manifest, SW lui-même).

**Pourquoi :**
- ✅ Cache SW extrêmement agressif (même après F5)
- ✅ manifest.json mis en cache aussi
- ✅ Seul moyen fiable : query string `?v=X`

**Pattern :**
```javascript
// sw.js
const CACHE_VERSION = 'maelya-v5'; // Bump à chaque changement critique

// manifest.json
{
  "icons": [
    { "src": "/icons/icon-192.png?v=4" }
  ]
}

// PushNotificationService.php
'icon' => url('/icons/icon-192.png?v=4')
```

**⚠️ ATTENTION :**
Bump `CACHE_VERSION` dans `sw.js` ne suffit PAS si les assets sont déjà en cache !
→ Toujours ajouter `?v=X` sur les URLs assets aussi.

**Checklist changement logo PWA :**
```
[ ] Remplacer fichiers PNG dans public/icons/
[ ] Bump CACHE_VERSION dans sw.js (v5 → v6)
[ ] Ajouter ?v=5 dans sw.js sur icon URLs
[ ] Ajouter ?v=5 dans manifest.json
[ ] Ajouter ?v=5 dans PushNotificationService.php
[ ] Tester en incognito
[ ] Unregister SW en DevTools
[ ] Réinstaller PWA
```

---

### 8. Numérotation Commandes : Recherche Globale

**Décision :** Générateur `Commande::genererNumero()` cherche dans TOUS les instituts.

**Pourquoi :**
```sql
-- Contrainte DB
ALTER TABLE commandes ADD UNIQUE KEY unique_numero (numero);
-- ⚠️ Pas de compound key (institut_id, numero) !
```

**Code :**
```php
// ❌ MAUVAIS : cherche par institut
$last = Commande::where('institut_id', $institutId)
    ->where('numero', 'like', "CMD-$date-%")
    ->orderBy('id', 'desc')
    ->first();
// → Institut A génère CMD-20260713-0005
// → Institut B génère CMD-20260713-0005
// → DUPLICATE KEY ERROR !

// ✅ BON : cherche globalement
$last = Commande::where('numero', 'like', "CMD-$date-%")
    ->lockForUpdate()  // Éviter race condition
    ->orderByRaw('CAST(SUBSTRING_INDEX(numero, \'-\', -1) AS UNSIGNED) DESC')
    ->first();
```

**LEÇON :** Toujours vérifier si contrainte UNIQUE est globale ou scopée avant d'écrire générateur.

**Bonus : lockForUpdate()** évite race condition si 2 commandes simultanées.

---

### 9. Livewire Caisse : Composant Stateful

**Décision :** Caisse = composant Livewire (stateful), pas Alpine.js.

**Pourquoi :**
- ✅ État panier persisté serveur (plus fiable que localStorage)
- ✅ Validation serveur en temps réel
- ✅ Calculs prix/stock côté serveur (sécurisé)
- ✅ Synchronisation multi-onglets/devices automatique
- ❌ Latence réseau (acceptable pour caisse fixe)

**Quand Livewire vs Alpine.js :**

| Critère | Livewire | Alpine.js |
|---------|----------|-----------|
| État serveur important | ✅ | ❌ |
| Validation complexe | ✅ | ❌ |
| Calculs sécurisés | ✅ | ❌ |
| Réactivité instantanée | ❌ | ✅ |
| Offline capable | ❌ | ✅ |
| SEO important | ✅ | ❌ |

**Exemples :**
- **Livewire :** Caisse, formulaires paiement, tableaux avec pagination serveur
- **Alpine.js :** Modals, dropdowns, accordéons, panier boutique publique

---

### 10. Boutique : Réouverture Modal sur Erreur

**Décision :** Modal commande se rouvre automatiquement si erreur serveur.

**Implementation :**
```php
// BoutiqueController.php
if ($validation_fails) {
    return back()
        ->withErrors(['telephone' => 'Format invalide'])
        ->withInput();
}

// index.blade.php
<div x-data="{
    commandeOpen: {{ session('error') || $errors->any() ? 'true' : 'false' }}
}">
```

**Pourquoi :**
- ✅ UX : utilisateur voit directement l'erreur dans le contexte
- ✅ Pas besoin de rescroller vers message flash
- ✅ Formulaire pré-rempli (withInput)

**Pattern général pour forms AJAX avec fallback :**
```blade
@if(session('success'))
    <div class="alert-success">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div x-data="{ show: true }" x-show="show">
        <!-- Afficher erreurs -->
    </div>
@endif
```

---

## 🚨 Erreurs Évitées et Leçons

### Bug #1 : Delete au lieu d'Update
**Cause :** `<form id="delete">` imbriqué dans `<form id="update">`

**Symptôme :** Bouton "Enregistrer" supprimait produit

**Leçon :** HTML ne supporte PAS forms imbriqués. Navigateur ignore balise `<form>` interne mais garde les inputs → `@method('DELETE')` écrase `@method('PUT')`.

**Fix :** Séparer forms complètement, masquer delete-form avec `display:none`.

---

### Bug #2 : :disabled Bloque Submit
**Cause :** `<button type="submit" :disabled="submitting">`

**Symptôme :** Form ne se soumet JAMAIS

**Leçon :** Alpine.js évalue `:disabled` pendant le handler du clic, AVANT que le form ne soumette. Si on set `submitting=true` dans `@click`, le bouton devient disabled et la soumission est annulée.

**Fix :** Utiliser `event.preventDefault()` dans handler JS, pas `:disabled`.

---

### Bug #3 : @json() Parse Error
**Cause :** `@json($data->map(fn($x) => ['bool' => (bool)$x->val]))`

**Symptôme :** Page blanche, aucune erreur PHP logs

**Leçon :** Parser Blade tokenize `fn()` et `(bool)` de manière ambiguë dans contexte `@json()`.

**Fix :** Pré-calculer dans `@php $data = ...; @endphp` puis `json_encode($data)`.

---

### Bug #4 : Cache Partiel Boutique
**Cause :** Update produit vidait cache `boutique_*` mais pas `caisse_*`

**Symptôme :** Boutique OK, mais caisse affichait ancien prix

**Leçon :** Une mutation peut affecter PLUSIEURS caches. Toujours invalider tous les caches concernés.

**Fix :**
```php
Cache::forget("boutique_{$institut->id}_produits");
Cache::forget("caisse_catalog_{$institut->id}");
```

---

### Bug #5 : Numéro Commande Dupliqué
**Cause :** `genererNumero()` filtrait par `institut_id`, mais contrainte UNIQUE globale

**Symptôme :** Duplicate key error aléatoire

**Leçon :** Vérifier portée contrainte DB avant d'écrire générateur de séquence.

**Fix :** Recherche globale + `lockForUpdate()`.

---

### Bug #6 : Ancien Logo PWA Persistant
**Cause :** Service Worker cache agressif

**Symptôme :** Nouveau logo pas affiché même après F5

**Leçon :** Cache SW survit à F5, Cmd+R, Clear browser cache. Seule solution : versioning query string.

**Fix :** `icon-192.png?v=4` + bump `CACHE_VERSION`.

---

## 📐 Patterns à Suivre

### Pattern : Contrôleur AJAX
```php
public function store(Request $request)
{
    $validated = $request->validate([...]);
    
    try {
        $model = Model::create($validated);
        
        // Invalider caches
        Cache::forget('key_' . $model->id);
        
        return response()->json([
            'success' => true,
            'message' => 'Créé avec succès',
            'data' => $model
        ]);
        
    } catch (\Exception $e) {
        Log::error('Erreur création: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la création'
        ], 500);
    }
}
```

### Pattern : Générateur Numéro Unique
```php
public static function genererNumero(string $prefix): string
{
    $date = now()->format('Ymd');
    $pattern = "$prefix-$date-%";
    
    // ⚠️ Recherche GLOBALE si contrainte globale
    $last = self::where('numero', 'like', $pattern)
        ->lockForUpdate()
        ->orderByRaw('CAST(SUBSTRING_INDEX(numero, \'-\', -1) AS UNSIGNED) DESC')
        ->first();
    
    $nextNumber = $last 
        ? ((int) substr($last->numero, -4)) + 1 
        : 1;
    
    $numero = sprintf("$prefix-$date-%04d", $nextNumber);
    
    // Retry si collision (ultra rare avec lock)
    if (self::where('numero', $numero)->exists()) {
        sleep(1);
        return self::genererNumero($prefix);
    }
    
    return $numero;
}
```

### Pattern : Cache Multi-Clés
```php
// Service/Repository layer
public function updateProduit(Produit $produit, array $data)
{
    $produit->update($data);
    
    // Invalider TOUS les caches affectés
    $cacheKeys = [
        "boutique_{$produit->institut_id}_produits",
        "caisse_catalog_{$produit->institut_id}",
        "produit_{$produit->id}_detail",
    ];
    
    foreach ($cacheKeys as $key) {
        Cache::forget($key);
    }
    
    return $produit->fresh();
}
```

### Pattern : Alpine.js avec Form Submit
```html
<form 
    method="POST" 
    action="..." 
    x-data="{ submitting: false }"
    @submit="handleSubmit"
>
    @csrf
    
    <button type="submit">
        <span x-show="!submitting">Envoyer</span>
        <span x-show="submitting">Envoi en cours...</span>
    </button>
</form>

<script>
function handleSubmit(event) {
    if (this.submitting) {
        event.preventDefault();
        return;
    }
    
    this.submitting = true;
    // Le form se soumet normalement
    // this.submitting sera reset au reload de page
}
</script>
```

---

## 🎓 Principes Généraux

1. **Toujours valider côté serveur** même si validation client existe
2. **Cache invalidation > Cache complexity** (vider cache est cheap)
3. **UUIDs partout** pour éviter collisions multi-tenant
4. **Global scopes prudents** (vérifier contraintes UNIQUE)
5. **Blade simple, logique en PHP** (pas de code complexe dans vues)
6. **AJAX pour UX, Forms pour fallback** (progressive enhancement)
7. **Versioning agressif assets** (SW, manifest, icons)
8. **lockForUpdate() pour séquences** (éviter race conditions)
9. **Logs généreux en dev** (retirer en prod)
10. **Tests en local AVANT push** (jamais exceptions)

---

**✅ Ces leçons ont été apprises à la dure. Les suivre économisera des heures de debug !**
