# 🛠️ Guide de Dépannage et Commandes Essentielles

**Date :** 17 Juillet 2026  
**Projet :** Maëlya Gestion

---

## 🚀 Commandes Essentielles

### Développement Local

```bash
# Démarrage serveurs
cd "/Volumes/Dev Disk/maelya-gestion/app"
php artisan serve                    # Terminal 1 → http://127.0.0.1:8000
npm run dev                          # Terminal 2 → http://localhost:5173

# Clear cache (après modifs config/routes/vues)
php artisan view:clear               # Cache Blade
php artisan config:clear             # Cache config
php artisan cache:clear              # Cache application
php artisan route:clear              # Cache routes

# Migration et seed
php artisan migrate                  # Appliquer migrations
php artisan migrate:fresh            # Reset DB + migrations
php artisan migrate:fresh --seed     # + seed data
php artisan db:seed                  # Seed uniquement

# Tests
php artisan test                     # PHPUnit
php artisan test --filter NomTest    # Test spécifique

# Vérification syntaxe
php -l app/Http/Controllers/MonController.php

# Storage link
php artisan storage:link             # Lien public/storage → storage/app/public

# Queues (si utilisées)
php artisan queue:work               # Traiter les jobs
php artisan queue:listen             # Mode watch
php artisan queue:restart            # Restart workers
```

---

### Déploiement Production (LWS)

```bash
# Depuis local
git add -A
git commit -m "🐛 Fix: description"
git push origin main

# Sur serveur LWS (SSH)
cd ~/maelya
git pull origin main
php artisan view:clear
php artisan config:clear
php artisan cache:clear

# Si nouvelle migration
php artisan migrate --force

# Si nouvelles dépendances
composer install --optimize-autoloader --no-dev
npm ci
npm run build
```

**⚠️ IMPORTANT :** Toujours tester en local avant de push !

---

### Logs et Debugging

```bash
# Voir logs temps réel
tail -f storage/logs/laravel.log

# Dernières 100 lignes
tail -100 storage/logs/laravel.log

# Chercher erreur spécifique
grep -i "error" storage/logs/laravel.log | tail -20

# Vider logs
echo "" > storage/logs/laravel.log

# Logs serveur web (LWS)
tail -f ~/logs/error_log
```

---

### Base de Données

```bash
# Entrer dans MySQL
mysql -u root -p
use maelya_dev;

# Ou via artisan
php artisan tinker

# Dans tinker
>>> App\Models\Produit::count()
>>> App\Models\Commande::latest()->first()
>>> Cache::flush()
```

---

### Git

```bash
# Status
git status
git log --oneline -10

# Diff avant commit
git diff
git diff --cached

# Annuler modifications non commitées
git checkout -- fichier.php
git restore fichier.php

# Annuler dernier commit (garder modifs)
git reset --soft HEAD~1

# Annuler dernier commit (supprimer modifs)
git reset --hard HEAD~1

# Voir historique d'un fichier
git log -p -- chemin/fichier.php
```

---

## 🐛 Erreurs Courantes et Solutions

### 1. Erreur 500 après modification

**Symptômes :**
- Page blanche
- "Server Error"

**Solutions :**
```bash
# 1. Vérifier syntaxe PHP
php -l app/Http/Controllers/MonController.php

# 2. Clear cache
php artisan config:clear
php artisan view:clear
php artisan cache:clear

# 3. Vérifier logs
tail -50 storage/logs/laravel.log

# 4. Permissions (si nécessaire)
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

### 2. Modifications Blade non visibles

**Symptômes :**
- Changements vues pas appliqués
- Ancienne version affichée

**Solutions :**
```bash
# 1. Clear cache Blade
php artisan view:clear

# 2. Hard refresh navigateur
# Mac: Cmd+Shift+R
# Win: Ctrl+Shift+R

# 3. Mode incognito pour tester

# 4. Vérifier cache navigateur
# DevTools → Network → Disable cache
```

---

### 3. Assets (CSS/JS) non chargés

**Symptômes :**
- 404 sur `/build/assets/...`
- Styles non appliqués

**Solutions :**
```bash
# 1. Vérifier Vite dev running
npm run dev

# 2. En production, rebuild
npm run build

# 3. Vérifier .env
VITE_DEV_SERVER_URL=http://localhost:5173

# 4. Clear cache navigateur

# 5. Vérifier vite.config.js
export default defineConfig({
    server: {
        hmr: {
            host: 'localhost',
        },
    },
});
```

---

### 4. Upload images échoue

**Symptômes :**
- "File too large"
- Upload silencieux

**Solutions :**
```bash
# 1. Vérifier storage link
php artisan storage:link
ls -la public/storage  # Doit pointer vers ../storage/app/public

# 2. Permissions
chmod -R 775 storage/app/public

# 3. Limites PHP (php.ini)
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300

# 4. Vérifier code
$request->validate([
    'photo' => 'required|image|max:5120', // 5MB
]);
```

---

### 5. Formulaire ne soumet pas

**Symptômes :**
- Bouton cliqué mais rien
- Pas de requête réseau

**Vérifications :**
```html
<!-- ❌ NE PAS FAIRE -->
<button type="submit" :disabled="submitting">Submit</button>

<!-- ✅ FAIRE -->
<button type="submit" @click="handleSubmit">
    <span x-text="submitting ? 'Envoi...' : 'Submit'"></span>
</button>

<script>
function handleSubmit(event) {
    if (this.submitting) {
        event.preventDefault();
        return;
    }
    this.submitting = true;
    // Form se soumet normalement
}
</script>
```

---

### 6. Cache non invalidé

**Symptômes :**
- Anciennes données affichées
- Modifications produit non visibles boutique

**Solutions :**
```php
// Dans Controller après store/update/destroy
use Illuminate\Support\Facades\Cache;

Cache::forget('boutique_' . $institut->id . '_produits');
Cache::forget('caisse_catalog_' . $institut->id);

// Ou vider tout le cache
Cache::flush();

// Via artisan
php artisan cache:clear
```

---

### 7. PWA : ancien logo affiché

**Symptômes :**
- Modifications icons pas visibles
- Anciennes icônes persistent

**Solutions :**
```bash
# 1. Versionner assets
/icons/icon-192.png?v=5  # Incrémenter le v

# 2. Bump Service Worker version
const CACHE_VERSION = 'maelya-v6'; // Dans sw.js

# 3. Unregister SW (DevTools)
# Application → Service Workers → Unregister

# 4. Clear storage
# Application → Storage → Clear site data

# 5. Réinstaller PWA
```

---

### 8. Numéro commande dupliqué

**Symptômes :**
- Erreur UNIQUE constraint `numero`
- "Duplicate entry 'CMD-...'"

**Vérification :**
```php
// ✅ BON : recherche globale
$lastCommande = self::where('numero', 'like', "CMD-$date-%")
    ->lockForUpdate()
    ->orderByRaw('CAST(SUBSTRING_INDEX(numero, \'-\', -1) AS UNSIGNED) DESC')
    ->first();

// ❌ MAUVAIS : recherche par institut
$lastCommande = self::where('institut_id', $institutId)
    ->where('numero', 'like', "CMD-$date-%")
    ->first();
```

---

### 9. Alpine.js ne fonctionne pas

**Symptômes :**
- `x-data` ignoré
- `x-show` ne toggle pas

**Vérifications :**
```html
<!-- 1. Alpine chargé ? -->
@vite(['resources/js/app.js'])

<!-- 2. Syntaxe correcte ? -->
<div x-data="{ open: false }">
    <button @click="open = !open">Toggle</button>
    <div x-show="open">Content</div>
</div>

<!-- 3. Pas de conflit jQuery/autre lib ? -->

<!-- 4. DevTools console pour erreurs JS -->
```

---

### 10. Migration échoue

**Symptômes :**
- "SQLSTATE[42S01]: Table exists"
- "Syntax error"

**Solutions :**
```bash
# 1. Rollback
php artisan migrate:rollback
php artisan migrate:rollback --step=1

# 2. Reset complet (⚠️ SUPPRIME DATA)
php artisan migrate:fresh

# 3. Voir status
php artisan migrate:status

# 4. Fix migration puis
php artisan migrate

# 5. En prod (force sans confirmation)
php artisan migrate --force
```

---

## 📋 Checklist Avant Commit

```
[ ] Syntaxe validée : php -l fichier.php
[ ] Cache vidé : php artisan view:clear
[ ] Testé en local navigateur
[ ] Logs vérifiés : tail storage/logs/laravel.log
[ ] Git diff relu
[ ] Message commit descriptif
[ ] @if/@endif balancés
[ ] Pas de dd() / var_dump() oubliés
[ ] Pas de console.log() debug oubliés
```

---

## 🔍 Patterns de Recherche Utiles

```bash
# Trouver tous les contrôleurs
find app/Http/Controllers -name "*.php"

# Chercher une classe/méthode
grep -r "class ProduitController" app/

# Chercher usage d'une fonction
grep -r "Cache::forget" app/

# Trouver vues Blade
find resources/views -name "*.blade.php"

# Chercher dans vues
grep -r "x-data" resources/views/

# Routes définies
php artisan route:list
php artisan route:list --name=produits
php artisan route:list | grep boutique
```

---

## 🎯 Commandes de Diagnostic

```bash
# Info système
php -v
composer --version
npm --version
node --version

# Info Laravel
php artisan about
php artisan env

# Vérifier config
php artisan config:show database
php artisan config:show app

# Permissions
ls -la storage/
ls -la bootstrap/cache/

# Espace disque
df -h
du -sh storage/

# Connexion DB
php artisan tinker
>>> DB::connection()->getPdo();
```

---

## 🔐 Sécurité

```bash
# Vérifier .env pas commité
git ls-files | grep .env  # Ne doit rien retourner

# Régénérer app key (⚠️ invalide sessions)
php artisan key:generate

# Vérifier fichiers sensibles
cat .gitignore | grep -E "\.env|node_modules|vendor"
```

---

## 📦 Composer et NPM

```bash
# Composer
composer install              # Installer dépendances
composer update               # Mettre à jour
composer dump-autoload        # Régénérer autoload
composer require vendor/package
composer remove vendor/package
composer show                 # Liste packages

# NPM
npm install                   # Installer node_modules
npm ci                        # Clean install (CI/CD)
npm update                    # Mettre à jour
npm run dev                   # Dev mode
npm run build                 # Production build
npm list --depth=0            # Liste packages
```

---

## ⚡ Optimisations Production

```bash
# Cache tout
php artisan config:cache      # Config
php artisan route:cache       # Routes
php artisan view:cache        # Vues Blade
php artisan event:cache       # Events

# Optimiser Composer
composer install --optimize-autoloader --no-dev

# Minifier assets
npm run build                 # Via Vite

# Clear tout avant re-cache
php artisan optimize:clear
```

---

## 🧪 Tests et Validation

```bash
# PHPUnit
php artisan test
php artisan test --filter=ProduitTest
php artisan test --testsuite=Feature

# Créer test
php artisan make:test ProduitTest
php artisan make:test ProduitTest --unit

# Syntax check multiple files
find app/ -name "*.php" -exec php -l {} \; | grep -v "No syntax errors"
```

---

## 📞 Aide Rapide

```bash
# Liste toutes commandes artisan
php artisan list

# Aide commande spécifique
php artisan help migrate
php artisan help make:controller

# Tinker (REPL)
php artisan tinker
>>> help
>>> exit
```

---

**✅ Garder ce fichier à portée de main pour dépannage rapide !**
