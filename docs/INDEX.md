# 📚 Index Documentation Projet Maëlya Gestion

**Date de création :** 17 Juillet 2026  
**Objectif :** Faciliter la reprise du projet sur un nouvel environnement

---

## 🎯 Documents Disponibles

### 1. [CONTEXTE-PROJET.md](./CONTEXTE-PROJET.md)
**📖 Vue d'ensemble complète du projet**

**Contenu :**
- Architecture technique (Laravel, Alpine.js, Livewire, Vite)
- Structure des dossiers détaillée
- Conventions de code et nommage
- Modules et fonctionnalités (Boutique, Caisse, Abonnements, PWA)
- Configuration environnement (.env local + production)
- Checklist démarrage nouvel ordinateur
- Design system et composants

**Quand l'utiliser :**
- ✅ Première prise en main du projet
- ✅ Comprendre l'architecture globale
- ✅ Setup nouvel environnement de dev
- ✅ Découvrir les fonctionnalités disponibles

---

### 2. [FICHIERS-MODIFIES-RECEMMENT.md](./FICHIERS-MODIFIES-RECEMMENT.md)
**🔧 Historique des modifications (13-17 Juillet 2026)**

**Contenu :**
- Liste détaillée des 10 fichiers critiques modifiés
- Raison de chaque modification
- Code avant/après pour les changements importants
- Bugs corrigés et leurs solutions
- Workflow appliqué
- Pièges évités

**Quand l'utiliser :**
- ✅ Comprendre le travail récent effectué
- ✅ Voir les patterns de correction de bugs
- ✅ Éviter de refaire les mêmes erreurs
- ✅ Contexte pour les derniers commits Git

---

### 3. [GUIDE-DEPANNAGE.md](./GUIDE-DEPANNAGE.md)
**🛠️ Commandes essentielles et dépannage**

**Contenu :**
- Commandes artisan, npm, git, composer
- Démarrage dev local (php artisan serve, npm run dev)
- Déploiement production LWS
- 10 erreurs courantes avec solutions
- Checklist avant commit
- Patterns de recherche dans le code
- Commandes de diagnostic et optimisation

**Quand l'utiliser :**
- ✅ Résoudre une erreur 500 / bug inattendu
- ✅ Rappel des commandes de déploiement
- ✅ Debug cache / assets / formulaires
- ✅ Référence rapide quotidienne

---

### 4. [DECISIONS-ARCHITECTURALES.md](./DECISIONS-ARCHITECTURALES.md)
**🧠 Pourquoi et comment : décisions techniques**

**Contenu :**
- 10 décisions architecturales majeures expliquées
- Pourquoi UUIDs v7 plutôt qu'auto-increment
- Pourquoi AJAX pour galerie plutôt que form classique
- Patterns à suivre (contrôleur AJAX, cache, générateur numéro)
- 6 bugs critiques résolus avec leçons apprises
- Principes généraux de développement

**Quand l'utiliser :**
- ✅ Comprendre POURQUOI le code est structuré ainsi
- ✅ Avant d'ajouter une fonctionnalité similaire
- ✅ Éviter de refaire des erreurs connues
- ✅ Onboarding développeur avancé

---

## 🚀 Parcours Recommandés

### 🆕 Je découvre le projet pour la première fois
```
1. CONTEXTE-PROJET.md (sections "Vue d'ensemble" + "Architecture")
2. README.md (à la racine du projet)
3. GUIDE-DEPANNAGE.md (section "Commandes Essentielles")
4. Lancer serveurs locaux : php artisan serve + npm run dev
5. Explorer le code via /dashboard
```

### 🐛 Je dois corriger un bug
```
1. GUIDE-DEPANNAGE.md (trouver l'erreur dans les 10 courantes)
2. FICHIERS-MODIFIES-RECEMMENT.md (bugs similaires déjà corrigés ?)
3. DECISIONS-ARCHITECTURALES.md (comprendre le contexte)
4. Vérifier logs : tail -f storage/logs/laravel.log
5. Appliquer fix + tester en local
```

### ✨ J'ajoute une nouvelle fonctionnalité
```
1. CONTEXTE-PROJET.md (conventions de code + structure)
2. DECISIONS-ARCHITECTURALES.md (patterns à suivre)
3. FICHIERS-MODIFIES-RECEMMENT.md (exemples récents similaires)
4. Coder en suivant les conventions
5. GUIDE-DEPANNAGE.md (checklist avant commit)
```

### 🖥️ Je setup un nouvel ordinateur
```
1. CONTEXTE-PROJET.md (section "Checklist Démarrage Nouvel Ordinateur")
2. Clone repo + composer install + npm install
3. Setup .env (copier de .env.example)
4. php artisan key:generate + migrate
5. GUIDE-DEPANNAGE.md (vérifier que tout fonctionne)
```

### 🚢 Je déploie en production
```
1. GUIDE-DEPANNAGE.md (section "Déploiement Production LWS")
2. Tester en local : php artisan test (si tests dispo)
3. git push origin main
4. SSH LWS : cd ~/maelya && git pull && php artisan cache:clear
5. Vérifier logs prod : tail -f ~/maelya/storage/logs/laravel.log
```

---

## 📊 Métriques du Projet

**Dernière mise à jour stats :** 17 Juillet 2026

- **Lignes de code :** ~35,000 (PHP + Blade + JS)
- **Fichiers modifiés récemment :** 10 fichiers critiques
- **Bugs corrigés (Juillet 2026) :** 12 bugs majeurs
- **Commits récents :** 15+ (période 13-17 Juillet)
- **Stack principal :** Laravel 12.56.0 + Alpine.js 3.x + Tailwind CSS
- **Base de données :** MySQL (UUIDs v7 primary keys)
- **Environnement prod :** LWS hébergement
- **PWA :** Service Worker v5

---

## 🔗 Liens Utiles

### Documentation Externe
- [Laravel 12.x Docs](https://laravel.com/docs/12.x)
- [Alpine.js Documentation](https://alpinejs.dev/start-here)
- [Livewire 3 Docs](https://livewire.laravel.com/docs)
- [Tailwind CSS](https://tailwindcss.com/docs)
- [Vite](https://vitejs.dev/guide/)

### Fichiers Clés du Projet
```
app/
├── app/Models/Commande.php          # Numérotation globale
├── app/Models/Produit.php           # Produits + cache
├── app/Http/Controllers/
│   ├── Dashboard/ProduitController.php
│   ├── Dashboard/ProduitImageController.php
│   └── BoutiqueController.php
├── app/Livewire/Caisse.php          # Caisse temps réel
├── app/Services/PushNotificationService.php
├── resources/views/
│   ├── dashboard/produits/form.blade.php
│   └── boutique/index.blade.php
├── public/sw.js                      # Service Worker
└── routes/web.php                    # Routes principales
```

### Fichiers Configuration
```
app/
├── .env                              # Config environnement
├── composer.json                     # Dépendances PHP
├── package.json                      # Dépendances JS
├── vite.config.js                    # Build config
├── tailwind.config.js                # CSS config
└── phpunit.xml                       # Tests config
```

---

## 🎓 Ressources d'Apprentissage

### Patterns Laravel Utilisés
- Global Scopes (multi-tenancy)
- UUID Primary Keys
- Service Layer (PushNotificationService)
- Mail Transactionnel (Mail\*)
- Queue Jobs (si implémentés)
- Cache Strategy (remember/forget)
- Eloquent Relations (belongsTo, hasMany, belongsToMany)

### Patterns Frontend Utilisés
- Alpine.js Components (x-data, x-show, @click)
- Fetch API pour AJAX
- LocalStorage pour panier
- Service Worker + Cache API
- Web Push API
- Progressive Enhancement

### Sécurité
- CSRF Protection (@csrf)
- SQL Injection Protection (Eloquent)
- XSS Protection ({{ }} échappement auto)
- File Upload Validation
- Role-Based Access Control

---

## 📞 Support

### En cas de blocage
1. **Vérifier logs :** `tail -f storage/logs/laravel.log`
2. **Consulter docs :** GUIDE-DEPANNAGE.md
3. **Chercher dans historique :** FICHIERS-MODIFIES-RECEMMENT.md
4. **Comprendre décision :** DECISIONS-ARCHITECTURALES.md

### Commandes de diagnostic rapide
```bash
# Status système
php artisan about

# Tester connexion DB
php artisan tinker
>>> DB::connection()->getPdo();

# Vérifier config
php artisan config:show app
php artisan config:show database

# Vider tout cache
php artisan optimize:clear
```

---

## ✅ Checklist Rapide

### Avant de commencer à coder
- [ ] J'ai lu CONTEXTE-PROJET.md (au moins overview)
- [ ] Serveurs locaux lancés (php artisan serve + npm run dev)
- [ ] DB connectée et migrée
- [ ] .env configuré correctement
- [ ] Git à jour (git pull origin main)

### Avant de commit
- [ ] Syntaxe validée : `php -l fichier.php`
- [ ] Testé en local navigateur
- [ ] Logs vérifiés (pas d'erreurs)
- [ ] Cache vidé : `php artisan view:clear`
- [ ] Git diff relu
- [ ] Message commit clair (🐛/✨/📝)

### Avant de push
- [ ] Tests passent (si tests existent)
- [ ] Pas de `dd()` / `var_dump()` / `console.log()` oubliés
- [ ] Pas de `.env` commité
- [ ] Commit atomique (1 feature/fix = 1 commit)

### Après déploiement
- [ ] Git pull sur serveur LWS réussi
- [ ] Caches vidés : `php artisan cache:clear`
- [ ] Site accessible (pas d'erreur 500)
- [ ] Logs prod vérifiés : `tail -f ~/maelya/storage/logs/laravel.log`
- [ ] Fonctionnalité testée en prod

---

## 🗂️ Organisation des Documents

```
docs/
├── INDEX.md                          ← CE FICHIER (point d'entrée)
├── CONTEXTE-PROJET.md                ← Vue d'ensemble complète
├── FICHIERS-MODIFIES-RECEMMENT.md    ← Historique modifications
├── GUIDE-DEPANNAGE.md                ← Commandes + debug
├── DECISIONS-ARCHITECTURALES.md      ← Pourquoi + patterns
├── api.md                            ← API documentation (si existe)
└── ...autres docs...
```

---

## 🎯 Objectifs de cette Documentation

✅ **Permettre à un autre agent/développeur de reprendre le projet en < 30 minutes**  
✅ **Éviter de refaire les mêmes erreurs (12 bugs déjà corrigés documentés)**  
✅ **Fournir contexte complet sans devoir lire 8000+ lignes de transcript**  
✅ **Référence rapide pour commandes courantes**  
✅ **Comprendre les décisions architecturales (le POURQUOI)**

---

## 📈 Prochaines Étapes Suggérées

1. **Tests Automatisés**
   - PHPUnit pour logique métier (Commande::genererNumero, etc.)
   - Feature tests pour parcours critiques (commande boutique, caisse)

2. **Monitoring et Alertes**
   - Sentry ou Bugsnag pour tracking erreurs prod
   - Alertes email sur erreurs critiques

3. **Performance**
   - Query optimization (N+1 detection)
   - Redis pour cache (actuellement file)
   - CDN pour assets statiques

4. **UX Améliorations**
   - Loading skeletons pour AJAX
   - Transitions Alpine.js
   - Keyboard shortcuts (caisse)

5. **Mobile**
   - Tests PWA sur vrais devices
   - Optimisations tactiles
   - Mode hors-ligne complet

---

**✨ Bonne continuation sur le projet Maëlya Gestion ! 🚀**

---

_Documentation générée le 17 Juillet 2026 par GitHub Copilot (Claude Sonnet 4.5)_
