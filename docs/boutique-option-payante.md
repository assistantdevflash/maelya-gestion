# Module Boutique en ligne — Option payante

> **Document d'approche et plan d'implémentation**  
> Date : 07/07/2026 | Projet : Maelya Gestion (Laravel 12)

---

## 📋 Résumé

Le module « Boutique en ligne » devient une **option payante** qui peut être ajoutée à **n'importe lequel des 3 plans d'abonnement existants** (Premium, Premium+, Ultra).

| Caractéristique | Valeur |
|---|---|
| **Prix** | 3 900 F CFA / mois |
| **Durée** | Toujours alignée sur la durée du plan choisi |
| **Essai** | Gratuit pendant les 14 jours d'essai |
| **Activation** | Case à cocher dans la souscription + option activable depuis la config boutique |

---

## 🏗️ Approche générale

### Principe

L'option boutique **n'est pas un 4ᵉ plan**. C'est un **add-on** qui s'ajoute au plan choisi :

```
Prix total = Prix du plan (selon période) + Option boutique (3 900 F × nb de mois)
```

### Architecture

```
┌──────────────────────────────────────────────────┐
│                  Abonnement                       │
│  ┌────────────┐  ┌─────────────────────────────┐ │
│  │ Plan       │  │ Options (metadata JSON)      │ │
│  │ (premium,  │  │ {                            │ │
│  │  premium+, │  │   "boutique": true,           │ │
│  │  ultra)    │  │   "boutique_prix": 3900       │ │
│  │            │  │ }                            │ │
│  └────────────┘  └─────────────────────────────┘ │
└──────────────────────────────────────────────────┘
```

Les options sont stockées dans le champ `metadata` (JSON) déjà existant dans la table `abonnements`. **Aucune migration de schéma n'est nécessaire.**

---

## 🔀 Flux utilisateur

```
Inscription
    │
    ▼
┌──────────┐  14 jours   ┌──────────────────────────┐
│  Essai   │ ──────────→ │  Souscription à un plan   │
│ Boutique │   gratuit   │  + case à cocher boutique  │
│ OFFERTE  │             │  (+3 900 F/mois)           │
└──────────┘             └──────────┬───────────────┘
                                    │
                          ┌─────────┴─────────┐
                          │                   │
                     Sans boutique      Avec boutique
                          │                   │
                          ▼                   ▼
                   Plan standard      Plan + boutique
                   (prix normal)      (prix + 3 900/mois)
```

---

## 📐 Étapes d'implémentation

### Étape 1 — Étendre le modèle `Abonnement`

**Fichier** : `app/Models/Abonnement.php`

Ajouter des accesseurs pour manipuler l'option boutique via `metadata` :

```php
/**
 * L'option boutique est-elle activée sur cet abonnement ?
 */
public function hasBoutique(): bool
{
    return (bool) ($this->metadata['boutique'] ?? false);
}

/**
 * Activer/désactiver l'option boutique
 */
public function setBoutique(bool $active, int $prix = 3900): void
{
    $meta = $this->metadata ?? [];
    $meta['boutique'] = $active;
    $meta['boutique_prix'] = $prix;
    $this->metadata = $meta;
}

/**
 * Prix mensuel de l'option boutique
 */
public function getBoutiquePrixMensuel(): int
{
    return $this->metadata['boutique_prix'] ?? 3900;
}

/**
 * Prix total de l'option boutique pour la période choisie
 */
public function getBoutiquePrixTotal(string $periode): int
{
    $nbMois = match($periode) {
        'mensuel' => 1,
        'annuel' => 12,
        'triennal' => 36,
        default => 1,
    };
    return $this->getBoutiquePrixMensuel() * $nbMois;
}
```

**Pour l'abonnement d'essai**, activer automatiquement la boutique :

Dans `App\Http\Controllers\Auth\InscriptionController@store` (après création de l'abonnement essai) :

```php
$abonnement->setBoutique(true, 0); // gratuit pendant l'essai
$abonnement->save();
```

---

### Étape 2 — Modifier la page de souscription (`plans.blade.php`)

**Fichier** : `resources/views/dashboard/abonnement/plans.blade.php`

Dans le modal de souscription, ajouter une **case à cocher** avant le formulaire de paiement :

```blade
{{-- Option boutique en ligne --}}
<div class="p-4 bg-gradient-to-r from-primary-50 to-secondary-50
            dark:from-primary-950/20 dark:to-secondary-950/20
            border-2 border-primary-200 dark:border-primary-700 rounded-2xl">
    <label class="flex items-start gap-3 cursor-pointer">
        <input type="checkbox" name="option_boutique" value="1"
               class="mt-1 w-5 h-5 rounded border-gray-300 text-primary-600
                      focus:ring-primary-500">
        <div>
            <span class="font-semibold text-gray-900 dark:text-white">
                🛍️ Ajouter la boutique en ligne
            </span>
            <span class="ml-2 text-sm font-bold text-primary-600 dark:text-primary-400">
                +3 900 F/mois
            </span>
            <p class="text-sm text-gray-600 dark:text-slate-400 mt-1">
                Vos clients pourront commander vos produits en ligne avec livraison à domicile
            </p>
        </div>
    </label>
</div>
```

Mettre à jour l'affichage du **prix total** en JavaScript pour refléter l'option :

```javascript
const nbMois = periode === 'mensuel' ? 1 : periode === 'annuel' ? 12 : 36;
const boutiquePrix = document.getElementById('option_boutique')?.checked
    ? 3900 * nbMois
    : 0;
const total = planPrix + boutiquePrix;
```

---

### Étape 3 — Modifier le contrôleur de souscription

**Fichier** : `app/Http/Controllers/Dashboard/AbonnementController.php`

Dans la méthode `souscrire()` :

```php
public function souscrire(Request $request, PlanAbonnement $plan)
{
    // ... validation existante ...

    $periode = $request->periode; // mensuel, annuel, triennal

    // Prix du plan
    $prixPlan = $plan->prixEffectif($periode);

    // Option boutique
    $optionBoutique = $request->boolean('option_boutique');
    $prixBoutique = 0;
    if ($optionBoutique) {
        $nbMois = match($periode) {
            'mensuel' => 1,
            'annuel' => 12,
            'triennal' => 36,
        };
        $prixBoutique = 3900 * $nbMois;
    }

    $montantTotal = $prixPlan + $prixBoutique;

    $abonnement = Abonnement::create([
        'user_id'    => Auth::id(),
        'plan_id'    => $plan->id,
        'montant'    => $montantTotal,
        'periode'    => $periode,
        'statut'     => 'en_attente',
        'debut_le'   => null, // sera défini à la validation
        'expire_le'  => null,
        'metadata'   => [
            'boutique' => $optionBoutique,
            'boutique_prix' => $optionBoutique ? 3900 : 0,
        ],
        // ... autres champs (reference_transfert, preuve_paiement, etc.)
    ]);

    // ... suite existante (notifications, etc.) ...
}
```

---

### Étape 4 — Modifier la validation admin

**Fichier** : `app/Http/Controllers/Admin/AdminAbonnementController.php`

Dans la méthode `valider()`, s'assurer que les métadonnées boutique sont préservées lors de la validation. Le champ `metadata` étant déjà sauvegardé correctement par le contrôleur de souscription, il n'y a normalement **aucune modification nécessaire** — sauf si la logique de validation écrase explicitement `metadata`.

---

### Étape 5 — Feature gate pour la boutique

**Fichier** : `config/plans-features.php`

Ajouter `boutique` dans les features de **tous les plans** (pour que la feature soit reconnue par le système de flags) :

```php
'premium' => [
    // ... existant ...
    'boutique',   // ← ajouter
],
'premium-plus' => ['*'],  // déjà *
'ultra'        => ['*'],  // déjà *
```

**Fichier** : `app/Models/User.php`

Ajouter une méthode pour vérifier si l'utilisateur a accès à la boutique :

```php
/**
 * L'utilisateur a-t-il accès au module boutique en ligne ?
 */
public function hasBoutiqueAccess(): bool
{
    // Super admin : toujours
    if ($this->isSuperAdmin()) return true;

    // Vérifier la feature de base
    if (!$this->aFonctionnalite('boutique')) return false;

    // Période d'essai : boutique gratuite
    $abo = $this->abonnementActif;
    if (!$abo) return false;

    // Plan essai → boutique offerte
    if ($abo->plan->slug === 'essai') return true;

    // Plan payant → vérifier l'option boutique
    return $abo->hasBoutique();
}
```

---

### Étape 6 — Protéger les routes et contrôleurs boutique

**Fichier** : `routes/web.php`

Ajouter le middleware de feature sur les routes boutique dashboard :

```php
Route::prefix('boutique')->name('boutique.')
    ->middleware('feature:boutique')
    ->group(function () {
        // ... routes existantes ...
    });
```

**Fichier** : `app/Http/Controllers/BoutiqueController.php`

Vérifier l'accès dans les méthodes publiques :

```php
public function index(string $slug)
{
    $institut = Institut::where('slug', $slug)->firstOrFail();

    if (!$institut->boutique_active) {
        abort(404);
    }
    // Vérifier que le propriétaire a l'option boutique
    if (!$institut->proprietaire?->hasBoutiqueAccess()) {
        abort(404);
    }
    // ...
}
```

---

### Étape 7 — Interface d'activation dans la config boutique

**Fichier** : `resources/views/dashboard/boutique/config.blade.php`

Si l'utilisateur n'a pas l'option boutique mais a un plan payant, afficher un bandeau d'upgrade au lieu du formulaire de configuration :

```blade
@php
    $hasBoutique = auth()->user()->hasBoutiqueAccess();
    $isEssai = auth()->user()->abonnementActif?->plan?->slug === 'essai';
@endphp

@if(!$hasBoutique && !$isEssai)
<div class="p-5 bg-amber-50 dark:bg-amber-950/30
            border-2 border-amber-200 dark:border-amber-800 rounded-2xl">
    <h3 class="font-semibold text-amber-800 dark:text-amber-200 text-lg">
        ⚠️ Module non inclus
    </h3>
    <p class="text-amber-700 dark:text-amber-300 mt-2">
        Le module Boutique en ligne n'est pas inclus dans votre abonnement actuel.
        Ajoutez-le pour <strong>3 900 F/mois</strong> et permettez à vos clients
        de commander en ligne.
    </p>
    <a href="{{ route('abonnement.plans') }}?ajouter=boutique"
       class="btn-primary mt-4 inline-flex">
        Activer la boutique (+3 900 F/mois)
    </a>
</div>
@else
    {{-- Formulaire de configuration normal --}}
@endif
```

---

### Étape 8 — Gérer l'ajout de l'option en cours d'abonnement

Créer une route et une méthode pour ajouter l'option boutique à un abonnement existant. Le paiement se fait via le flux manuel existant.

**Fichier** : `routes/web.php`

```php
Route::post('abonnement/ajouter-option-boutique',
    [AbonnementController::class, 'ajouterOptionBoutique'])
    ->name('abonnement.ajouter-boutique');
```

**Fichier** : `app/Http/Controllers/Dashboard/AbonnementController.php`

```php
public function ajouterOptionBoutique(Request $request)
{
    $abo = auth()->user()->abonnementActif;
    if (!$abo || $abo->plan->slug === 'essai') {
        return back()->with('error', 'Action non disponible.');
    }
    if ($abo->hasBoutique()) {
        return back()->with('info', 'Option boutique déjà activée.');
    }

    // Calculer le prorata pour le reste de la période
    $joursRestants = max(1, $abo->joursRestants());
    $prixJournalier = 3900 / 30;
    $montantProrata = (int) round($prixJournalier * $joursRestants);

    // Créer une demande d'ajout d'option (en_attente)
    $nouvelAbo = Abonnement::create([
        'user_id'    => auth()->id(),
        'plan_id'    => $abo->plan_id,
        'montant'    => $montantProrata,
        'periode'    => 'option_boutique',
        'statut'     => 'en_attente',
        'metadata'   => [
            'type' => 'ajout_option_boutique',
            'abonnement_source_id' => $abo->id,
            'boutique' => true,
            'boutique_prix' => 3900,
        ],
    ]);

    // Notifier les admins
    // ...

    return redirect()->route('abonnement.plans')
        ->with('success', 'Demande envoyée. Veuillez finaliser le paiement.');
}
```

**Logique de validation** : Dans `AdminAbonnementController@valider()`, lorsqu'un abonnement de type `ajout_option_boutique` est validé, au lieu de remplacer l'abonnement actif, on met à jour son `metadata` :

```php
if (($abonnement->metadata['type'] ?? '') === 'ajout_option_boutique') {
    $aboSource = Abonnement::find($abonnement->metadata['abonnement_source_id']);
    if ($aboSource) {
        $aboSource->setBoutique(true);
        $aboSource->save();
    }
    $abonnement->update(['statut' => 'actif']); // marquer comme traité
    return back()->with('success', 'Option boutique activée.');
}
```

---

### Étape 9 — Bloquer l'accès public à la boutique

**Fichier** : `app/Http/Controllers/BoutiqueController.php`

Dans `index()` et toutes les méthodes publiques :

```php
// Vérifier que le propriétaire a l'option boutique (ou est en essai)
if (!$institut->proprietaire?->hasBoutiqueAccess()) {
    abort(404, 'Cette boutique n\'est pas disponible.');
}
```

---

## 📊 Tableau récapitulatif des fichiers à modifier

| N° | Fichier | Action |
|---|---|---|
| 1 | `app/Models/Abonnement.php` | Ajouter `hasBoutique()`, `setBoutique()`, `getBoutiquePrixMensuel()`, `getBoutiquePrixTotal()` |
| 2 | `app/Models/User.php` | Ajouter `hasBoutiqueAccess()` |
| 3 | `config/plans-features.php` | Ajouter `boutique` dans les features premium |
| 4 | `app/Http/Controllers/Auth/InscriptionController.php` | Activer boutique sur l'abonnement essai |
| 5 | `app/Http/Controllers/Dashboard/AbonnementController.php` | Modifier `souscrire()` + ajouter `ajouterOptionBoutique()` |
| 6 | `app/Http/Controllers/Admin/AdminAbonnementController.php` | Gérer la validation `ajout_option_boutique` |
| 7 | `resources/views/dashboard/abonnement/plans.blade.php` | Ajouter case à cocher boutique + prix dynamique |
| 8 | `resources/views/dashboard/boutique/config.blade.php` | Ajouter bandeau upgrade si pas d'option |
| 9 | `app/Http/Controllers/BoutiqueController.php` | Vérifier `hasBoutiqueAccess()` |
| 10 | `routes/web.php` | Ajouter middleware `feature:boutique` + route ajout option |

---

## 💰 Calcul des prix

| Période | Nb mois | Option boutique |
|---|---|---|
| **Mensuel** | 1 | 3 900 F |
| **Annuel** | 12 | 46 800 F |
| **Triennal** | 36 | 140 400 F |

---

## 🔒 Règles métier

1. ✅ L'essai (14 jours) inclut **gratuitement** la boutique
2. ✅ L'option boutique peut être **ajoutée en cours d'abonnement** (prorata)
3. ✅ L'option boutique est **liée à l'abonnement** : si l'abonnement expire, la boutique devient inaccessible
4. ✅ Le renouvellement d'abonnement **conserve** l'option boutique
5. ✅ L'upgrade de plan (ex: Premium → Premium+) **conserve** l'option boutique
6. ✅ Super admin : toujours accès (pour support/démo)
7. ✅ La boutique publique (`/shop/{slug}`) renvoie 404 si le propriétaire n'a pas l'option

---

## ⏱️ Estimation

| Étape | Temps estimé |
|---|---|
| Étape 1 — Modèle Abonnement | 15 min |
| Étape 2 — Vue plans.blade | 30 min |
| Étape 3 — Contrôleur souscription | 20 min |
| Étape 4 — Validation admin | 5 min |
| Étape 5 — Feature gate | 10 min |
| Étape 6 — Middleware routes | 10 min |
| Étape 7 — Bandeau upgrade config | 20 min |
| Étape 8 — Ajout en cours d'abonnement | 30 min |
| Étape 9 — Blocage boutique publique | 10 min |
| **Total** | **~2h30** |
