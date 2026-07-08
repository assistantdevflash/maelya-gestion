# 🚀 Optimisations de Performance - Maelya Gestion

## 📊 Objectif
Réduire le LCP (Largest Contentful Paint) de 30s+ à <2.5s pour une expérience utilisateur fluide.

## ✅ Optimisations Implémentées

### 1. **Vite Configuration Avancée**
- ✅ Code splitting automatique (Alpine.js, Axios séparés)
- ✅ Minification Terser avec suppression console.log
- ✅ CSS minification et code split
- ✅ Assets optimisés par type (images, CSS, JS)
- ✅ Tree shaking pour réduire bundle size

### 2. **HTML & Ressources Critiques**
- ✅ Inline du script theme (anti-flash)
- ✅ DNS prefetch & preconnect pour CDN
- ✅ Livewire styles inline (pas de requête bloquante)
- ✅ Livewire scripts defer avec inline
- ✅ Lazy loading des images avec placeholder SVG

### 3. **JavaScript Optimisé**
- ✅ Import dynamique de caisse.js (lazy load)
- ✅ Alpine.js géré par Livewire (optimisé)
- ✅ Form submit handlers efficaces
- ✅ Pas de code bloquant au chargement

### 4. **Middleware Performance**
- ✅ Cache headers pour assets statiques (1 an)
- ✅ Immutable flag pour fichiers hashés
- ✅ Compression hints
- ✅ Security headers optimisés

### 5. **Cache Laravel**
- ✅ View caching (Blade compilé)
- ✅ Config caching
- ✅ Route caching
- ✅ Event caching
- ✅ Autoloader optimisé

### 6. **Component Lazy Image**
```blade
<x-lazy-image 
    src="/path/to/image.jpg" 
    alt="Description"
    width="400"
    height="300"
    class="rounded-lg"
/>
```

## 🛠️ Commandes

### Production (Déploiement)

⚠️ **IMPORTANT** : Build assets en local avant de push (Node.js non installé sur serveur)

```bash
# EN LOCAL : Build des assets
npm run build
git add public/build
git commit -m "Build production"
git push origin main

# SUR SERVEUR : Optimisation Laravel uniquement
cd ~/maelya
git pull origin main
php artisan app:optimize
# OU
bash scripts/optimize-production.sh
```

### Développement
```bash
# Désactiver tous les caches
php artisan optimize:clear

# Dev server avec HMR
npm run dev
```

## 📈 Résultats Attendus

| Métrique | Avant | Après | Objectif |
|----------|-------|-------|----------|
| **LCP** | 5-32s | <2.5s | <2.5s (Good) |
| **FID** | Variable | <100ms | <100ms (Good) |
| **CLS** | OK | <0.1 | <0.1 (Good) |
| **TTI** | 5-15s | <3s | <3.8s (Good) |
| **Bundle Size** | ~500KB | ~200KB | <300KB |

## 🔍 Monitoring

### Chrome DevTools
1. Lighthouse (Performance audit)
2. Network tab (Check asset loading)
3. Coverage tab (Unused code)
4. Performance tab (LCP timing)

### Core Web Vitals
- LCP : Premier élément visible principal
- FID : Temps de réponse à la première interaction
- CLS : Stabilité visuelle (pas de décalages)

## 📝 Checklist Déploiement

- [ ] `npm run build` exécuté
- [ ] `php artisan app:optimize` exécuté
- [ ] Cache serveur vidé (opcache, redis si applicable)
- [ ] Assets déployés (dossier `public/build`)
- [ ] Permissions storage/cache OK
- [ ] Test Lighthouse sur une page représentative
- [ ] Vérifier LCP < 2.5s

## 🐛 Troubleshooting

### LCP encore élevé ?
1. Vérifier les images : utiliser WebP, tailles optimisées
2. Fonts : déjà optimisées (display=swap)
3. CSS : critical CSS inline si nécessaire
4. DB queries : eager loading (N+1)
5. Serveur : PHP opcache activé

### Assets non chargés ?
```bash
php artisan storage:link
php artisan view:clear
npm run build
```

### Cache Laravel bloqué ?
```bash
php artisan optimize:clear
php artisan cache:clear
```

## 🚀 Améliorations Futures

1. **CDN** : Servir assets statiques depuis CDN
2. **HTTP/2** : Push de ressources critiques
3. **WebP** : Conversion automatique des images
4. **Service Worker** : Cache offline avancé
5. **Redis** : Cache session/queries
6. **Lazy Components** : Alpine components lazy-loaded

## 📚 Ressources

- [Web.dev - Core Web Vitals](https://web.dev/vitals/)
- [Laravel Performance Best Practices](https://laravel.com/docs/deployment#optimization)
- [Vite Performance Guide](https://vitejs.dev/guide/performance.html)
- [Alpine.js Performance](https://alpinejs.dev/advanced/performance)
