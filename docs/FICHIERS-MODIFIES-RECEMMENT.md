# 📝 Fichiers Modifiés Récemment

**Période :** 13-17 Juillet 2026  
**Contexte :** Corrections bugs critiques + amélioration boutique + local dev setup

---

## 🔥 Fichiers Critiques Modifiés

### 1. `/app/resources/views/dashboard/produits/form.blade.php`
**Modifications :** 
- ✅ Ajout `mx-auto` pour centrage
- ✅ Remplacement toggles CSS par Alpine.js
- ✅ Galerie déplacée hors du `<form>` principal
- ✅ Badges visuels (Boutique/Masqué/Vedette)
- ✅ Pré-calcul `$imagesData` dans `@php` au lieu de `@json()`
- ✅ **CRITIQUE:** `delete-form` déplacé HORS du `produit-form`

**Raison :** Formulaire imbriqué causait suppression au lieu d'update

**Lignes clés :**
```blade
@php
    $imagesData = ($images ?? collect())->map(function($img) {
        return [
            'id' => $img->id,
            'url' => asset('storage/' . $img->chemin),
            'is_principale' => $img->is_principale ? true : false,
        ];
    })->values()->toArray();
@endphp

<!-- Formulaire principal -->
<form id="produit-form" method="POST" action="...">
    @csrf
    @method('PUT')
    <!-- Tous les champs produit -->
</form>

<!-- Formulaire delete SÉPARÉ -->
<form id="delete-form" method="POST" action="..." style="display:none">
    @csrf
    @method('DELETE')
</form>
```

---

### 2. `/app/Http/Controllers/Dashboard/ProduitController.php`
**Modifications :**
- ✅ Ajout `Cache::forget('boutique_' . $institutId . '_produits')` dans :
  - `store()`
  - `update()`
  - `destroy()`

**Raison :** Boutique affichait anciennes données après modification produit

**Code ajouté :**
```php
use Illuminate\Support\Facades\Cache;

// Dans store(), update(), destroy()
Cache::forget('boutique_' . $institutId . '_produits');
Cache::forget('caisse_catalog_' . $institutId);
```

---

### 3. `/app/Http/Controllers/Dashboard/ProduitImageController.php`
**Modifications :**
- ✅ Toutes méthodes retournent JSON (pas de redirect)
- ✅ Cache clearing double (boutique + caisse)
- ✅ Gestion erreurs avec codes HTTP appropriés

**Méthodes :**
```php
public function store(Request $request, string $id)  // POST /dashboard/produits/{id}/images
public function destroy(string $produitId, string $imageId)  // DELETE
public function setPrincipale(string $produitId, string $imageId)  // PATCH
```

**Tous retournent :**
```php
return response()->json([
    'success' => true,
    'message' => '...',
    'data' => [...]
]);
```

---

### 4. `/app/Http/Controllers/BoutiqueController.php`
**Modifications :**
- ✅ Ajout filtre `visible_boutique` (true OU NULL pour compatibilité)
- ✅ Vérification `hasBoutiqueAccess()` avant affichage boutique
- ✅ Cache 1h sur catalogue

**Méthode `produit()` :**
```php
public function produit(Request $request, string $slug, string $produitSlug)
{
    $institut = Institut::where('slug', $slug)->firstOrFail();

    if (!$institut->user->hasBoutiqueAccess()) {
        abort(403, 'Boutique non disponible');
    }

    $produit = $institut->produits()
        ->where('slug', $produitSlug)
        ->where('actif', true)
        ->where(function($q) {
            $q->whereNull('visible_boutique')->orWhere('visible_boutique', true);
        })
        ->with('images')
        ->firstOrFail();

    return view('boutique.produit', compact('institut', 'produit'));
}
```

---

### 5. `/app/resources/views/boutique/index.blade.php`
**Modifications :**
- ✅ Retrait `:disabled="submitting"` du bouton submit
- ✅ Ajout affichage erreur inline dans modal
- ✅ Réouverture automatique modal si erreur serveur
- ✅ Anti double-clic avec flag `submitting`

**Code Alpine.js :**
```javascript
boutique() {
    return {
        commandeOpen: {{ session('error') || $errors->any() ? 'true' : 'false' }},
        submitting: false,
        
        handleSubmit(event) {
            if (this.submitting) {
                event.preventDefault();
                return;
            }
            this.submitting = true;
            // Formulaire se soumet normalement
        }
    }
}
```

**Bouton :**
```html
<button type="submit" 
        class="btn-primary w-full"
        @click="handleSubmit"
        x-text="submitting ? 'Envoi en cours...' : 'Confirmer la commande'">
</button>
```

---

### 6. `/app/Models/Commande.php`
**Modifications :**
- ✅ `genererNumero()` recherche maintenant GLOBALEMENT (pas par institut)
- ✅ Ajout `lockForUpdate()` pour éviter race conditions
- ✅ Cast UNSIGNED pour tri numérique correct

**Méthode complète :**
```php
public static function genererNumero(string $institutId): string
{
    $date = now()->format('Ymd');
    $prefixe = "CMD-$date-";

    // ⚠️ RECHERCHE GLOBALE (pas de where('institut_id'))
    $lastCommande = self::where('numero', 'like', "CMD-$date-%")
        ->lockForUpdate()
        ->orderByRaw('CAST(SUBSTRING_INDEX(numero, \'-\', -1) AS UNSIGNED) DESC')
        ->first();

    if ($lastCommande) {
        $lastNumber = (int) substr($lastCommande->numero, -4);
        $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $newNumber = '0001';
    }

    $numero = $prefixe . $newNumber;

    // Retry si collision (ultra rare avec lock)
    if (self::where('numero', $numero)->exists()) {
        sleep(1);
        return self::genererNumero($institutId);
    }

    return $numero;
}
```

**Raison :** Contrainte `UNIQUE` sur `numero` est globale, pas par institut

---

### 7. `/public/sw.js`
**Modifications :**
- ✅ Version bumped : `maelya-v4` → `maelya-v5`
- ✅ Icons versionés : `?v=4`
- ✅ Badge notifications : `/icons/badge-72.png?v=4`

**Variables :**
```javascript
const CACHE_VERSION = 'maelya-v5';

// Dans fetch event
const iconUrl = new URL('/icons/icon-192.png?v=4', self.location.origin).href;
const badgeUrl = new URL('/icons/badge-72.png?v=4', self.location.origin).href;
```

---

### 8. `/public/manifest.json`
**Modifications :**
- ✅ Tous les icons versionés : `?v=4`

**Exemple :**
```json
{
  "icons": [
    {
      "src": "/icons/icon-192.png?v=4",
      "sizes": "192x192",
      "type": "image/png",
      "purpose": "any"
    }
  ]
}
```

---

### 9. `/app/Services/PushNotificationService.php`
**Modifications :**
- ✅ Icon URL : `/icons/icon-192.png?v=4`
- ✅ Badge URL : `/icons/badge-72.png?v=4`

**Méthodes :**
```php
public static function sendToUser(User $user, string $titre, string $corps, ?string $url = null)
{
    $payload = [
        'title' => $titre,
        'body' => $corps,
        'icon' => url('/icons/icon-192.png?v=4'),
        'badge' => url('/icons/badge-72.png?v=4'),
        'url' => $url ?? url('/dashboard'),
    ];
    // ...
}
```

---

### 10. `/public/icons/badge-72.png` & `badge-96.png`
**Modifications :**
- ✅ Nouveaux badges générés : silhouette blanche du logo
- ✅ Transparence PNG
- ✅ Format Android status bar (monochrome)

**Script génération (Python PIL) :**
```python
from PIL import Image, ImageDraw
img = Image.open('Logo_Maëlya_Icone_1.png').convert('RGBA')
white_img = Image.new('RGBA', img.size, (255, 255, 255, 0))
for x in range(img.width):
    for y in range(img.height):
        r, g, b, a = img.getpixel((x, y))
        if a > 128:
            white_img.putpixel((x, y), (255, 255, 255, a))
white_img.thumbnail((72, 72), Image.Resampling.LANCZOS)
white_img.save('badge-72.png')
```

---

## 📋 Fichiers de Documentation Créés

### `/docs/CONTEXTE-PROJET.md`
**Contenu :**
- Vue d'ensemble complète du projet
- Architecture technique
- Conventions de code
- Bugs résolus
- Points critiques
- Checklist démarrage

### `/docs/FICHIERS-MODIFIES-RECEMMENT.md`
**Contenu :** Ce fichier actuel

---

## 🔄 Workflow Appliqué

```bash
# Pour chaque modification :
1. Identifier le problème
2. Chercher fichiers concernés (grep/semantic search)
3. Lire contexte (read_file)
4. Modifier (replace_string_in_file)
5. Valider syntaxe (php -l)
6. Tester en local
7. Commit + Push
8. Rappeler commande déploiement
```

---

## ⚠️ Pièges Évités

| Piège | Fichier | Solution |
|-------|---------|----------|
| `@json()` avec cast | form.blade.php | Pré-calcul @php |
| Form imbriqué | form.blade.php | Séparation totale |
| :disabled sur submit | index.blade.php | Flag JS uniquement |
| Cache partiel | *Controller.php | Double forget (boutique+caisse) |
| Race condition CMD | Commande.php | lockForUpdate() |
| Cache SW agressif | sw.js + manifest | Versioning ?v=X |

---

## 🎯 Prochaines Étapes Suggérées

1. **Tests automatisés :** PHPUnit pour logique métier critique
2. **Monitoring :** Logs erreurs + alertes email
3. **Performance :** Query optimization (N+1, indexes)
4. **UX :** Loading skeletons pour AJAX
5. **Mobile :** Tests PWA sur vrais devices

---

**✅ Tous les fichiers sont validés, testés et poussés sur `main`.**
