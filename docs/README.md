# 📚 Documentation Maëlya Gestion

**Version :** 2.0  
**Date :** 17 Juillet 2026  
**Statut :** Complet et à jour

---

## 🎯 Bienvenue !

Cette documentation complète vous permettra de reprendre le projet Maëlya Gestion en moins de 30 minutes, que vous soyez un nouvel agent IA, un développeur qui rejoint l'équipe, ou que vous travailliez sur un nouvel ordinateur.

**Ce que vous trouverez ici :**
- ✅ Contexte complet du projet (architecture, stack, conventions)
- ✅ Historique des modifications récentes (bugs corrigés, leçons apprises)
- ✅ Guide de dépannage (commandes, erreurs courantes)
- ✅ Décisions architecturales (pourquoi et comment)
- ✅ Notes du repository (workflow, pièges, patterns)
- ✅ Checklists et parcours recommandés

---

## 📖 Documents Disponibles

| Document | Description | Quand l'utiliser |
|----------|-------------|------------------|
| **[INDEX.md](INDEX.md)** | 🗺️ **Point d'entrée principal**<br>Vue d'ensemble de la documentation + parcours recommandés | **TOUJOURS COMMENCER ICI** |
| **[CONTEXTE-PROJET.md](CONTEXTE-PROJET.md)** | 🏗️ Architecture technique complète<br>Stack, structure, modules, configuration | Première prise en main<br>Setup nouvel environnement |
| **[FICHIERS-MODIFIES-RECEMMENT.md](FICHIERS-MODIFIES-RECEMMENT.md)** | 🔧 Historique modifications (Juillet 2026)<br>10 fichiers critiques + bugs corrigés | Comprendre travail récent<br>Contexte derniers commits |
| **[GUIDE-DEPANNAGE.md](GUIDE-DEPANNAGE.md)** | 🛠️ Commandes et solutions<br>10 erreurs courantes, diagnostics | Résoudre bugs<br>Référence quotidienne |
| **[DECISIONS-ARCHITECTURALES.md](DECISIONS-ARCHITECTURALES.md)** | 🧠 Pourquoi et comment<br>10 décisions majeures, patterns, leçons | Comprendre choix techniques<br>Ajouter fonctionnalité similaire |
| **[NOTES-REPOSITORY.md](NOTES-REPOSITORY.md)** | 📝 Spécificités du projet<br>Workflow, Blade tips, pièges, conventions | Connaître les spécificités<br>Éviter erreurs connues |

---

## 🚀 Démarrage Rapide (3 minutes)

### 1️⃣ Première Fois sur le Projet
```bash
# 1. Lire la documentation (5 min max)
docs/INDEX.md → "Je découvre le projet pour la première fois"

# 2. Setup environnement
git clone [URL] maelya-gestion
cd maelya-gestion/app
composer install
npm install
cp .env.example .env
php artisan key:generate

# 3. Base de données
# Créer DB locale
php artisan migrate
php artisan db:seed  # optionnel

# 4. Storage
php artisan storage:link

# 5. Lancer serveurs
php artisan serve    # Terminal 1 → http://127.0.0.1:8000
npm run dev          # Terminal 2 → http://localhost:5173
```

### 2️⃣ Reprendre le Projet sur Nouvel Ordinateur
```bash
# 1. Pull dernières modifs
git pull origin main

# 2. Dépendances
composer install
npm install

# 3. Lancer serveurs
php artisan serve
npm run dev

# 4. Lire contexte récent
docs/FICHIERS-MODIFIES-RECEMMENT.md
```

### 3️⃣ Corriger un Bug
```bash
# 1. Reproduire le bug
# 2. Vérifier logs
tail -f storage/logs/laravel.log

# 3. Consulter guide
docs/GUIDE-DEPANNAGE.md → "Erreurs Courantes"

# 4. Vérifier si bug déjà corrigé
docs/FICHIERS-MODIFIES-RECEMMENT.md → "Bugs Corrigés"
```

---

## 📋 Commande de Déploiement (IMPORTANT)

**⚠️ TOUJOURS utiliser cette commande après un push vers production :**

```bash
cd ~/maelya && git pull origin main && php artisan view:clear && php artisan cache:clear
```

**Si nouvelle migration :**
```bash
php artisan migrate --force
```

**Détails :** Voir [NOTES-REPOSITORY.md](NOTES-REPOSITORY.md#-commande-de-déploiement-lws)

---

## 🗺️ Parcours Recommandés

### 🆕 Découverte du Projet
```
1. INDEX.md (section "Vue d'ensemble")
2. CONTEXTE-PROJET.md (sections 1-2-3)
3. Lancer serveurs locaux
4. Explorer /dashboard dans le navigateur
```

### 🐛 Résolution de Bug
```
1. GUIDE-DEPANNAGE.md (10 erreurs courantes)
2. FICHIERS-MODIFIES-RECEMMENT.md (bugs similaires ?)
3. DECISIONS-ARCHITECTURALES.md (contexte)
4. Appliquer fix + tester
5. GUIDE-DEPANNAGE.md (checklist avant commit)
```

### ✨ Nouvelle Fonctionnalité
```
1. CONTEXTE-PROJET.md (conventions)
2. DECISIONS-ARCHITECTURALES.md (patterns à suivre)
3. NOTES-REPOSITORY.md (workflow)
4. Développer en suivant conventions
5. GUIDE-DEPANNAGE.md (checklist)
6. Déployer avec commande standard
```

---

## 🎯 Structure de la Documentation

```
docs/
├── README.md                         ← CE FICHIER (point d'entrée docs)
├── INDEX.md                          ← Navigation et parcours
│
├── CONTEXTE-PROJET.md                ← Architecture et setup
├── FICHIERS-MODIFIES-RECEMMENT.md    ← Historique changements
├── GUIDE-DEPANNAGE.md                ← Commandes et debug
├── DECISIONS-ARCHITECTURALES.md      ← Décisions techniques
├── NOTES-REPOSITORY.md               ← Spécificités projet
│
├── api.md                            ← API documentation (si existe)
└── ...autres...
```

---

## ✅ Checklist de Qualité

### Avant de Coder
- [ ] J'ai lu [INDEX.md](INDEX.md) et [CONTEXTE-PROJET.md](CONTEXTE-PROJET.md)
- [ ] Serveurs locaux lancés et fonctionnels
- [ ] Git à jour (`git pull origin main`)
- [ ] Je connais les conventions du projet

### Avant de Commit
- [ ] Syntaxe validée : `php -l fichier.php`
- [ ] Testé en local navigateur
- [ ] Logs vérifiés (aucune erreur)
- [ ] Cache vidé : `php artisan view:clear`
- [ ] Message commit clair (🐛/✨/📝)

### Avant de Push
- [ ] Tests passent (si tests existent)
- [ ] Pas de debug code oublié
- [ ] `.env` non commité
- [ ] Commit atomique

### Après Déploiement
- [ ] Git pull sur serveur réussi
- [ ] Caches vidés
- [ ] Site accessible
- [ ] Logs prod vérifiés

---

## 🔑 Informations Clés

### Stack Technique
- **Backend :** Laravel 12.56.0 (PHP 8.3+)
- **Frontend :** Blade + Alpine.js 3.x + Livewire 3.x
- **CSS :** Tailwind CSS 3.x
- **Build :** Vite 7.3.2
- **Database :** MySQL (UUIDs v7)
- **Hébergement :** LWS

### URLs
- **Dev local :** http://127.0.0.1:8000
- **Production :** https://maelyagestion.com
- **Vite dev :** http://localhost:5173

### Git Repository
- **Repo :** assistantdevflash/maelya-gestion
- **Branche principale :** `main`
- **Derniers commits :** Corrections critiques Juillet 2026

---

## 📞 Support et Ressources

### Documentation Externe
- [Laravel 12.x](https://laravel.com/docs/12.x)
- [Alpine.js](https://alpinejs.dev)
- [Livewire 3](https://livewire.laravel.com)
- [Tailwind CSS](https://tailwindcss.com)

### En Cas de Blocage
1. Vérifier logs : `tail -f storage/logs/laravel.log`
2. Consulter [GUIDE-DEPANNAGE.md](GUIDE-DEPANNAGE.md)
3. Chercher dans [FICHIERS-MODIFIES-RECEMMENT.md](FICHIERS-MODIFIES-RECEMMENT.md)
4. Comprendre décision dans [DECISIONS-ARCHITECTURALES.md](DECISIONS-ARCHITECTURALES.md)

---

## 🎓 Ce Que Vous Apprendrez

En lisant cette documentation, vous maîtriserez :

✅ **Architecture :** Multi-tenancy, UUIDs v7, cache strategy  
✅ **Patterns :** AJAX forms, Alpine.js components, Livewire stateful  
✅ **Sécurité :** CSRF, XSS, SQL injection protection  
✅ **PWA :** Service Worker, manifest, push notifications  
✅ **Déploiement :** LWS workflow, cache invalidation  
✅ **Bugs :** 12+ bugs critiques et leurs solutions documentées  
✅ **Best Practices :** Conventions code, tests, commits  

---

## 📊 Métriques du Projet

**Au 17 Juillet 2026 :**

- **Lignes de code :** ~35,000 (PHP + Blade + JS)
- **Fonctionnalités :** 14/14 priorités implémentées (100%)
- **Bugs corrigés (Juillet 2026) :** 12 bugs majeurs
- **Commits récents :** 15+ (période 13-17 Juillet)
- **Tests :** Feature tests pour fonctionnalités critiques
- **Documentation :** 6 documents complets (~5000 lignes)

---

## 🚀 Prochaines Étapes Suggérées

1. **Tests Automatisés :** PHPUnit pour logique critique
2. **Monitoring :** Sentry/Bugsnag pour prod
3. **Performance :** Redis cache, CDN, query optimization
4. **UX :** Loading skeletons, transitions
5. **Mobile :** Tests PWA sur vrais devices

---

## ✨ Objectifs de cette Documentation

Cette documentation vise à :

1. ✅ **Permettre reprise projet en < 30 minutes**
2. ✅ **Éviter de refaire les mêmes erreurs** (12 bugs documentés)
3. ✅ **Fournir contexte complet** sans lire 8000+ lignes de transcript
4. ✅ **Référence rapide** pour commandes et patterns
5. ✅ **Comprendre le POURQUOI** des décisions architecturales

---

## 🎉 Prêt à Continuer !

**👉 Commencez par [INDEX.md](INDEX.md) pour choisir votre parcours !**

---

_Documentation générée le 17 Juillet 2026 par GitHub Copilot (Claude Sonnet 4.5)_  
_Maintenue par l'équipe de développement Maëlya Gestion_

**Version :** 2.0  
**Dernière mise à jour :** 17 Juillet 2026  
**Statut :** ✅ Complet et validé
