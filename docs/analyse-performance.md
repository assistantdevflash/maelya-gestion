# 🔬 Analyse de performance — Maëlya Gestion

> Date : 23 juin 2026  
> Application : Laravel 11 + Livewire 4.2 + Tailwind CSS

---

## 📊 SYNTHÈSE : OÙ PART LE TEMPS

> ✅ **P0 résolu le 23/06/2026** — commit `94c52f1`

Chaque page du dashboard subit **3 couches de requêtes** qui s'empilent :

```
┌─────────────────────────────────────────────────┐
│  MIDDLEWARE AbonnementActif  →  5-7 requêtes   │  ← sur TOUTES les pages
├─────────────────────────────────────────────────┤
│  View Composer (sidebar anniversaires) → 1 req  │  ← sur TOUTES les pages
├─────────────────────────────────────────────────┤
│  Controller (ex: Dashboard@index) → 23+ req     │  ← variable
└─────────────────────────────────────────────────┘
Total minimum par page dashboard : ~7-8 requêtes AVANT le contrôleur
```

---

## 🔴 PROBLÈMES CRITIQUES (P0) — ✅ TOUS RÉSOLUS

> **Commit** : `94c52f1` — 23 juin 2026

### 1. ✅ `DashboardController@index` → **23 requêtes → ~10 requêtes**

**Fichier** : `app/Http/Controllers/Dashboard/DashboardController.php` (lignes 16-210)

Chaque KPI déclenche sa propre requête :

| # | Variable | Requête | Table |
|---|----------|---------|-------|
| 1 | `caJour` | `SUM(total)` WHERE date = today | `ventes` |
| 2 | `caMois` | `SUM(total)` WHERE date >= début mois | `ventes` |
| 3 | `nbClients` | `COUNT(*)` WHERE actif = true | `clients` |
| 4 | `ventesJour` | `COUNT(*)` WHERE date = today | `ventes` |
| 5 | `ventesMois` | `COUNT(*)` WHERE date >= début mois | `ventes` |
| 6 | `nouveauxClientsJour` | `COUNT(*)` WHERE created_at = today | `clients` |
| 7 | `produitsEnAlerte` | `COUNT(*)` WHERE stock <= seuil | `produits` |
| 8 | `depensesMois` | `SUM(montant)` WHERE date >= début mois | `depenses` |
| 9 | `paiementsCash` | `SUM(total)` WHERE mode = cash | `ventes` |
| 10 | `paiementsMobile` | `SUM(total)` WHERE mode = mobile_money | `ventes` |
| 11 | `paiementsCarte` | `SUM(total)` WHERE mode = carte | `ventes` |
| 12 | `paiementsMixte` | `SUM(total)` WHERE mode = mixte | `ventes` |
| 13 | `ventesParJour` | `SUM(total) GROUP BY date` (30 jours) | `ventes` |
| 14 | `dernieresVentes` | `SELECT ... LIMIT 5` | `ventes` |
| 15 | `alertesStock` | `SELECT ... LIMIT 5` | `produits` |
| 16 | `abonnement` | Relation `hasOne` | `abonnements` |
| 17 | `caJourPrec` | `SUM(total)` WHERE date = yesterday | `ventes` |
| 18 | `caMoisPrec` | `SUM(total)` WHERE date mois précédent | `ventes` |
| 19 | `ventesJourPrec` | `COUNT(*)` WHERE date = yesterday | `ventes` |
| 20 | `ventesMoisPrec` | `COUNT(*)` WHERE date mois précédent | `ventes` |
| 21 | `nouveauxClientsJourPrec` | `COUNT(*)` WHERE created_at = yesterday | `clients` |
| 22 | `cadeauClientIds` | `pluck('client_id')` | `codes_reduction` |
| 23 | `anniversairesAujourdhui` | `SELECT ... WHERE date_naissance = m-d` | `clients` |

**🔧 Solution** : Fusionner les requêtes sur la même table en agrégations conditionnelles :

```php
$stats = Vente::where('statut', 'validee')
    ->selectRaw("
        SUM(CASE WHEN DATE(created_at) = ? THEN total ELSE 0 END) as ca_jour,
        COUNT(CASE WHEN DATE(created_at) = ? THEN 1 END) as ventes_jour,
        SUM(CASE WHEN DATE(created_at) >= ? AND DATE(created_at) <= ? THEN total ELSE 0 END) as ca_mois,
        COUNT(CASE WHEN DATE(created_at) >= ? AND DATE(created_at) <= ? THEN 1 END) as ventes_mois,
        SUM(CASE WHEN mode_paiement = 'cash' AND DATE(created_at) >= ? THEN total ELSE 0 END) as cash,
        SUM(CASE WHEN mode_paiement = 'mobile_money' AND DATE(created_at) >= ? THEN total ELSE 0 END) as mobile,
        SUM(CASE WHEN mode_paiement = 'carte' AND DATE(created_at) >= ? THEN total ELSE 0 END) as carte,
        SUM(CASE WHEN mode_paiement = 'mixte' AND DATE(created_at) >= ? THEN total ELSE 0 END) as mixte
    ", [...bindings])->first();
```

→ Passe de **13 requêtes à 1** pour les stats ventes.

---

### 2. ✅ Middleware `AbonnementActif` → **0 requête après le 1er hit**

**Fichier** : `app/Http/Middleware/AbonnementActif.php` (lignes 1-130)

| Ligne | Requête | Condition |
|-------|---------|-----------|
| 55 | `$user->institut` (relation `belongsTo`) | Toujours |
| 61 | `Institut::find()` | Si employé |
| 62 | `User::find()` | Si employé |
| 63 | `$owner->abonnementActif` (relation `hasOne`) | Toujours |
| 64 | `$owner->abonnementEnSursis()` (whereHas + first) | Si pas d'abonnement actif |
| 103 | `$abonnementUser->abonnements()->...->exists()` | Si pas d'abonnement ni sursis |
| 108 | `$abonnementUser->abonnements()->...->first()` | Si pas d'abonnement ni sursis |

**🔧 Solution appliquée** : Le middleware `AbonnementActif` stocke le statut en session (`session(['abo_status' => ...])`) après le premier calcul. Les requêtes suivantes lisent la session → **0 requête DB**.

---

### 3. ✅ Cache applicatif activé

Aucun `Cache::remember()`, `cache()`, ou Redis trouvé dans le code métier (`app/app/`). Toutes les données sont recalculées à chaque requête.

**🔧 Solution appliquée** :
- Compteur anniversaires sidebar → `Cache::remember('anniv_count_' . $institutId, now()->addHour(), ...)`
- Statut abonnement → session (voir P0-2)
- KPIs dashboard → fusionnés en 1 requête (voir P0-1)
- Reste à faire (P1) : catalogue Caisse, stats financières

---

### 4. ✅ Index de base de données ajoutés

| Table | Index manquant | Requête impactée |
|-------|---------------|------------------|
| `users` | `institut_id` | `where('institut_id', ...)` dans RdvController, VenteController, DashboardController |
| `users` | `(role, actif)` | Filtres employés/admin actifs |
| `clients` | `date_naissance` | Requête anniversaire exécutée sur **chaque page dashboard** |
| `ventes` | `user_id` | Filtre par employé dans historique ventes |
| `ventes` | `client_id` | Filtre dans fiche client |
| `plans_abonnement` | `(actif, ordre)` | ✅ Ajouté | `where('actif', true)->orderBy('ordre')` utilisé partout |

> Migration : `2026_06_23_141804_add_missing_performance_indexes.php`

**🔧 Solution appliquée** : Migration `2026_06_23_141804_add_missing_performance_indexes` ajoutant les 6 index.

```sql
ALTER TABLE users ADD INDEX users_institut_id_idx (institut_id);
ALTER TABLE users ADD INDEX users_role_actif_idx (role, actif);
ALTER TABLE clients ADD INDEX clients_date_naissance_idx (date_naissance);
ALTER TABLE ventes ADD INDEX ventes_user_id_idx (user_id);
ALTER TABLE ventes ADD INDEX ventes_client_id_idx (client_id);
ALTER TABLE plans_abonnement ADD INDEX plans_actif_ordre_idx (actif, ordre);
```

---

## 🟡 PROBLÈMES IMPORTANTS (P1) — ✅ TOUS RÉSOLUS

> **Commit** : `470d7b0` — 23 juin 2026

### 5. ✅ Composant Livewire `Caisse.php` → **catalogue en cache 1h**

**Fichier** : `app/Livewire/Caisse.php` (lignes 265-330)

Chaque interaction (recherche client, changement catégorie) relance :
- 4 requêtes catalogue (prestations + produits + catégories avec `whereHas`)
- 4 requêtes redondantes (`allCatPrestations` et `allCatProduits` sans `whereHas`)
- 1 requête 200 clients

**🔧 Solution appliquée** :
- `Cache::remember('caisse_catalog_' . $institutId, 3600, ...)` — catalogue (prestations, produits, catégories) en cache 1h
- `allCatPrestations` et `allCatProduits` fusionnées : une seule requête par type de catégorie, les listes filtrées (`catPrestations`/`catProduits`) dérivées en PHP
- `allClients` reste hors cache (change plus souvent)

### 6. ✅ Middleware `RequireFeature` → **0 requête après le 1er hit**

**Fichier** : `app/Http/Middleware/RequireFeature.php`

`User::aFonctionnalite()` appelle `planActuelSlug()` → `abonnementActif` (1 req) → `abonnements()->first()` (1 req).

**🔧 Solution appliquée** : `planActuelSlug()` utilise `session('plan_slug_' . $this->id)` pour cacher le résultat. Après le 1er calcul → 0 requête DB.

### 7. ✅ `VenteController@caisse` → `Client::all()` supprimé

**Fichier** : `app/Http/Controllers/Dashboard/VenteController.php` (ligne 32)

Redondant avec le composant Livewire Caisse qui charge déjà sa propre liste de clients.

**🔧 Solution appliquée** : Requête supprimée du contrôleur.

### 8. ✅ N+1 dans `ClientController@show` corrigé

**Fichier** : `app/Http/Controllers/Dashboard/ClientController.php` (ligne 134)

```php
$client->rendezVous()->with('prestations')->latest('debut_le')->take(50)->get();
```

**🔧 Solution appliquée** : `->with('prestations')` ajouté.

### 9. ✅ `UPPER(code)` remplacé par `where('code', ...)`

**Fichier** : `app/Livewire/Caisse.php` (ligne ~196) + `CodeReductionController.php` (ligne 128)

**🔧 Solution appliquée** : Les codes étant toujours stockés en majuscules, `->where('code', $input)` remplace `->whereRaw('UPPER(code) = ?', ...)`. L'index unique sur `(institut_id, code)` est maintenant utilisé.

---

## 🟢 PROBLÈMES MODÉRÉS (P2-P3)

| # | Problème | Fichier | Solution |
|---|----------|---------|----------|
| 10 | `CodeReductionController` → `get()` sans pagination | `app/Http/Controllers/Dashboard/CodeReductionController.php:22-24` | `->paginate(30)` |
| 11 | `RdvController` → `Client::all()` + `Prestation::all()` sans limite | `app/Http/Controllers/Dashboard/RdvController.php:52-53` | `->limit(200)` |
| 12 | `FinanceController@rapport` → `get()` sans limite | `app/Http/Controllers/Dashboard/FinanceController.php:276` | Paginer |
| 13 | `FinanceController@index` → 4 paires de requêtes quasi-identiques | `app/Http/Controllers/Dashboard/FinanceController.php:90-175` | Unifier avec UNION/CASE WHEN |
| 14 | `maatwebsite/excel` inutilisé | `composer.json` | Supprimer si non utilisé |
| 15 | Pas de Redis configuré | `.env` | Cache/session/queue passent par MySQL |
| 16 | `SESSION_LIFETIME=43200` (30 jours) | `.env` | Les sessions s'accumulent en base |

---

## 📈 IMPACT ESTIMÉ DES OPTIMISATIONS

| Optimisation | Gain estimé | Complexité |
|-------------|-------------|------------|
| Fusion requêtes Dashboard | **-200ms** | Moyenne |
| Cache catalogue Caisse | **-150ms** | Faible |
| Cache statut abonnement (session) | **-100ms** | Faible |
| Index DB manquants | **-30% requêtes lentes** | Faible |
| Cache KPIs dashboard (5 min) | **-300ms** | Moyenne |
| Debounce recherche client | **-80ms par frappe** | Très faible |
| Suppression requêtes redondantes | **-50ms** | Très faible |
| **TOTAL potentiel** | **~500-900ms par page** | |

---

## 🎯 PLAN D'ACTION

### Étape 1 — Gains rapides ✅ TERMINÉE (23 juin 2026)

| Action | Statut | Fichier(s) |
|--------|--------|-----------|
| Fusionner les requêtes Dashboard | ✅ Fait | `DashboardController.php` |
| Ajouter 6 index DB manquants | ✅ Fait | Migration `2026_06_23_141804` |
| Mettre le statut abonnement en session | ✅ Fait | `AbonnementActif.php` |
| `Cache::remember` compteur anniversaires sidebar | ✅ Fait | `AppServiceProvider.php` |
| `wire:model.debounce` recherche client | ✅ Déjà présent | `caisse.blade.php` (Alpine) |

### Étape 2 — Structurel ✅ TERMINÉE (23 juin 2026)

| Action | Statut | Fichier(s) |
|--------|--------|-----------|
| Fusionner les requêtes Dashboard | ✅ Fait (P0) | `DashboardController.php` |
| `Cache::remember` catalogue Caisse | ✅ Fait | `Caisse.php` |
| Corriger N+1 `ClientController@show` | ✅ Fait | `ClientController.php` |
| Supprimer `Client::all()` redondant | ✅ Fait | `VenteController.php` |
| Ajouter pagination sur `get()` sans limite | ✅ Fait | `CodeReductionController.php`, `RdvController.php` |
| Cacher `planActuelSlug()` en session | ✅ Fait | `User.php` |
| Remplacer `UPPER(code)` par index natif | ✅ Fait | `Caisse.php`, `CodeReductionController.php` |

### Étape 3 — Long terme

| Action | Effort |
|--------|--------|
| Installer Redis (cache + session + queue) | 1-2h |
| Scinder le composant Caisse en sous-composants | 2-3h |
| Ajouter colonne `code_upper` pour l'index | 30 min |
| Nettoyer `composer.json` (packages inutilisés) | 15 min |

---

*Rapport généré le 23 juin 2026 — Application Maëlya Gestion*
