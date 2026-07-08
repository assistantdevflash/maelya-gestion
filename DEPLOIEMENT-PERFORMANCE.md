# 🎯 DÉPLOIEMENT PERFORMANCE - Actions Immédiates

## 📦 1. Build en local (OBLIGATOIRE)
```bash
cd "/Volumes/Dev Disk/maelya-gestion/app"

# Build des assets avec Vite
npm run build

# Commit et push (avec les assets buildés)
git add -A
git commit -m "Build production assets"
git push origin main
```

⚠️ **IMPORTANT** : Node.js n'est pas installé sur le serveur LWS. Tous les builds doivent être faits en local et pushés dans le repo.

## 🖥️ 2. Sur le serveur LWS (SSH)
```bash
cd ~/maelya

# Pull du code (avec les assets buildés)
git pull origin main

# Clear ancien cache
php artisan view:clear
php artisan config:clear
php artisan cache:clear

# Réinstaller dépendances optimisées
composer install --no-dev --optimize-autoloader

# Activer les caches
php artisan view:cache
php artisan config:cache
php artisan route:cache
php artisan event:cache

# Permissions
chmod -R 755 storage bootstrap/cache
chmod -R 775 storage/logs
```

## ✅ 3. Vérification Post-Déploiement

### Test Performance
1. Ouvrir DevTools (F12)
2. Onglet Lighthouse
3. "Generate report" en mode Performance
4. Vérifier LCP < 2.5s ✅

### Checklist
- [ ] Page se charge rapidement (<3s)
- [ ] Pas d'erreurs console JavaScript
- [ ] Images chargent progressivement
- [ ] Styles appliqués immédiatement
- [ ] Navigation fluide entre pages

## 🔧 Si problème de cache

```bash
# Désactiver tous les caches
php artisan optimize:clear

# Reconstruire
php artisan view:cache
php artisan config:cache
php artisan route:cache
```

## 📊 Résultats Attendus

| Métrique | Avant | Après |
|----------|-------|-------|
| **LCP** | 5-32s 🔴 | <2.5s 🟢 |
| **Bundle JS** | ~500KB | ~48KB |
| **Bundle CSS** | ~250KB | ~219KB |
| **Requêtes** | 50+ | 30- |

## 🎨 Assets Optimisés

### JavaScript (48KB total)
- `app.js` : 2.8 KB (code principal)
- `caisse.js` : 8.2 KB (lazy loaded)
- `vendor.js` : 37 KB (Axios)
- `alpine.js` : 0 KB (géré par Livewire)

### CSS
- `app.css` : 219 KB (minifié, critical inline)

## 💡 Nouveaux Outils

### Commande d'optimisation
```bash
php artisan app:optimize
```

### Component Lazy Image
```blade
<x-lazy-image 
    src="/storage/images/photo.jpg"
    alt="Description"
    width="800"
    height="600"
    class="rounded-lg"
/>
```

### Script bash complet
```bash
bash scripts/optimize-production.sh
```

## 📞 Support

En cas de problème :
1. Vérifier logs : `storage/logs/laravel.log`
2. Clear cache : `php artisan optimize:clear`
3. Rebuild : `npm run build`
4. Check permissions : `ls -la storage/`

---

✅ **Une fois déployé, tester immédiatement avec Lighthouse !**
