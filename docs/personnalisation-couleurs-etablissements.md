# Personnalisation des couleurs — Établissements Maëlya Gestion

> **Version :** 1.0 — 31 juillet 2026  
> **Statut :** Analyse technique — En attente de validation

---

## Table des matières

1. [Concept](#1-concept)
2. [Base de données](#2-base-de-données)
3. [Interface utilisateur](#3-interface-utilisateur)
4. [Implémentation technique](#4-implémentation-technique)
5. [Factures & Devis PDF](#5-factures--devis-pdf)
6. [Emails transactionnels](#6-emails-transactionnels)
7. [Boutique en ligne](#7-boutique-en-ligne)
8. [Dashboard (interface admin)](#8-dashboard-interface-admin)
9. [Contraste & accessibilité](#9-contraste--accessibilité)
10. [Checklist de déploiement](#10-checklist-de-déploiement)

---

## 1. Concept

Chaque établissement peut choisir **3 couleurs** qui seront appliquées sur :

- 🧾 Factures et devis PDF
- 📧 Emails (confirmation commande, notification, etc.)
- 🛍️ Boutique en ligne publique
- 🎨 Interface dashboard (optionnel)

**Couleurs par défaut (Maëlya) :**

| Rôle | Hex | Aperçu |
|---|---|---|
| Principale | `#7c3aed` | 🟣 Violet |
| Secondaire | `#ec4899` | 🩷 Rose |
| Accent | `#f59e0b` | 🟠 Ambre |

---

## 2. Base de données

### 2.1 Migration

```php
// database/migrations/xxxx_xx_xx_add_couleurs_to_instituts.php

Schema::table('instituts', function (Blueprint $table) {
    $table->string('couleur_primaire', 7)->default('#7c3aed')->after('boutique_conditions');
    $table->string('couleur_secondaire', 7)->default('#ec4899')->after('couleur_primaire');
    $table->string('couleur_accent', 7)->default('#f59e0b')->after('couleur_secondaire');
});
```

### 2.2 Modèle `Institut`

```php
// Ajouter à $fillable
'couleur_primaire', 'couleur_secondaire', 'couleur_accent',
```

### 2.3 Helper — Détection texte clair ou foncé

```php
// app/Helpers/CouleurHelper.php ou méthode sur Institut

/**
 * Détermine si une couleur hex est foncée (retourne true → texte blanc).
 * Basé sur la luminance relative WCAG.
 */
function estFoncee(string $hex): bool
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    // Luminance relative
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    return $luminance < 0.5;
}
```

---

## 3. Interface utilisateur

### 3.1 Route

```php
// Dans routes/web.php, groupe dashboard → role:admin
Route::get('parametres/apparence', [ApparenceController::class, 'edit'])
    ->name('parametres.apparence');
Route::put('parametres/apparence', [ApparenceController::class, 'update'])
    ->name('parametres.apparence.update');
Route::post('parametres/apparence/reset', [ApparenceController::class, 'reset'])
    ->name('parametres.apparence.reset');
```

### 3.2 Menu sidebar

```
Paramètres
├── Général
├── Apparence       ← nouveau
└── ...
```

### 3.3 Vue : `resources/views/dashboard/parametres/apparence.blade.php`

```
┌────────────────────────────────────────────────────────┐
│  🎨 Apparence de l'établissement                       │
│                                                        │
│  Personnalisez les couleurs de vos factures, emails    │
│  et votre boutique en ligne.                           │
│                                                        │
│  ┌────────────────────────────────────────────────┐   │
│  │  Couleur principale                             │   │
│  │  [🟣] [#7c3aed]  ← input type="color"          │   │
│  │  Utilisée pour : boutons, en-têtes, titres      │   │
│  └────────────────────────────────────────────────┘   │
│                                                        │
│  ┌────────────────────────────────────────────────┐   │
│  │  Couleur secondaire                             │   │
│  │  [🩷] [#ec4899]                                 │   │
│  │  Utilisée pour : badges, accents, survols       │   │
│  └────────────────────────────────────────────────┘   │
│                                                        │
│  ┌────────────────────────────────────────────────┐   │
│  │  Couleur d'accent                               │   │
│  │  [🟠] [#f59e0b]                                 │   │
│  │  Utilisée pour : icônes, liens, mises en avant  │   │
│  └────────────────────────────────────────────────┘   │
│                                                        │
│  ┌────────────────────────────────────────────────┐   │
│  │  📱 Aperçu en temps réel                        │   │
│  │                                                  │   │
│  │  ┌──────────────────────────────────────┐      │   │
│  │  │  ┌──────────┐  ┌──────┐              │      │   │
│  │  │  │ Bouton   │  │Badge│              │      │   │
│  │  │  │ principal│  └──────┘              │      │   │
│  │  │  └──────────┘                        │      │   │
│  │  │                                      │      │   │
│  │  │  ───────────────────────────────     │      │   │
│  │  │  FACTURE #001                        │      │   │
│  │  │  Sous-titre client                  │      │   │
│  │  │  ───────────────────────────────     │      │   │
│  │  └──────────────────────────────────────┘      │   │
│  └────────────────────────────────────────────────┘   │
│                                                        │
│  [💾 Enregistrer]  [↺ Réinitialiser aux défauts]      │
└────────────────────────────────────────────────────────┘
```

### 3.4 Aperçu en temps réel (Alpine.js)

```blade
<div x-data="{
    primaire: '{{ $institut->couleur_primaire }}',
    secondaire: '{{ $institut->couleur_secondaire }}',
    accent: '{{ $institut->couleur_accent }}'
}">
    {{-- Inputs couleur avec x-model --}}
    <input type="color" x-model="primaire" name="couleur_primaire">
    <input type="color" x-model="secondaire" name="couleur_secondaire">
    <input type="color" x-model="accent" name="couleur_accent">

    {{-- Aperçu dynamique --}}
    <div :style="'background:' + primaire" class="text-white px-4 py-2 rounded-lg">
        Bouton principal
    </div>
    <div :style="'background:' + secondaire" class="text-white px-3 py-1 rounded-full text-xs">
        Badge
    </div>
</div>
```

---

## 4. Implémentation technique

### 4.1 Injection CSS dans le `<head>` du layout

Fichier : `resources/views/layouts/dashboard.blade.php`

```blade
{{-- Après le chargement du CSS --}}
@php $institut = auth()->user()?->institut; @endphp
@if($institut)
<style>
    :root {
        --couleur-primaire: {{ $institut->couleur_primaire }};
        --couleur-secondaire: {{ $institut->couleur_secondaire }};
        --couleur-accent: {{ $institut->couleur_accent }};
    }
</style>
@endif
```

### 4.2 Utilisation dans Blade

```blade
{{-- Bouton --}}
<button style="background: var(--couleur-primaire)" class="text-white px-4 py-2 rounded-xl hover:opacity-90 transition">
    Valider
</button>

{{-- Badge --}}
<span style="background: var(--couleur-secondaire)" class="text-white px-2 py-0.5 rounded-full text-xs">
    Nouveau
</span>

{{-- Lien --}}
<a style="color: var(--couleur-accent)" href="#" class="hover:underline">
    Voir plus
</a>

{{-- Dégradé --}}
<div style="background: linear-gradient(135deg, var(--couleur-primaire), var(--couleur-secondaire))">
    Hero section
</div>
```

### 4.3 Texte clair/foncé automatique

```blade
{{-- Helper PHP dans la vue --}}
@php
    $texteBouton = \App\Helpers\CouleurHelper::estFoncee($institut->couleur_primaire) ? 'white' : '#1f2937';
@endphp

<button style="background: var(--couleur-primaire); color: {{ $texteBouton }}">
    Bouton lisible quelle que soit la couleur
</button>
```

---

## 5. Factures & Devis PDF

### 5.1 Injection des couleurs dans le PDF

Fichier : `resources/views/pdf/facture.blade.php`

```blade
@php
    $c1 = $institut->couleur_primaire;
    $c2 = $institut->couleur_secondaire;
@endphp

<style>
    .entete { background-color: {{ $c1 }}; color: white; }
    .total  { color: {{ $c1 }}; font-weight: bold; }
    .badge-statut { background-color: {{ $c2 }}; color: white; }
</style>
```

### 5.2 Fichiers à modifier

| Fichier | Éléments à colorer |
|---|---|
| `pdf/facture.blade.php` | En-tête, total, badge statut |
| `pdf/devis.blade.php` | En-tête, total, badge statut |
| `pdf/facture-module.blade.php` | En-tête, lignes total |
| `pdf/facture-commande.blade.php` | En-tête, lignes total |
| `pdf/ticket.blade.php` | Logo, total |
| `pdf/bon-commande.blade.php` | En-tête, lignes |
| `pdf/credit.blade.php` | En-tête, statut |
| `pdf/fiche-client.blade.php` | Titres, badges |

---

## 6. Emails transactionnels

### 6.1 Injection des couleurs dans les emails

Fichier : `resources/views/mail/*.blade.php`

```blade
@component('mail::message')
@php $c1 = $institut->couleur_primaire; @endphp

# Bonjour {{ $client->prenom }}

{{-- Bouton avec couleur personnalisée --}}
@component('mail::button', ['url' => $url, 'color' => 'custom'])
    Voir ma commande
@endcomponent

{{-- Ligne colorée --}}
<div style="border-top: 3px solid {{ $c1 }}; margin: 20px 0;"></div>
```

### 6.2 Fichiers à modifier

| Fichier | Éléments |
|---|---|
| `mail/NouvelleCommandeClient.blade.php` | Bouton, bordure |
| `mail/AbonnementValide.blade.php` | En-tête, bouton |
| `mail/AbonnementRejete.blade.php` | En-tête |
| `mail/BienvenueMaelya.blade.php` | En-tête, boutons |
| `mail/CommandeStatutUpdatedClient.blade.php` | Bouton, badge statut |

---

## 7. Boutique en ligne

### 7.1 Injection CSS dans le layout public

Fichier : `resources/views/boutique/layouts/app.blade.php`

```blade
{{-- Avant </head> --}}
@if($institut)
<style>
    :root {
        --couleur-primaire: {{ $institut->couleur_primaire }};
        --couleur-secondaire: {{ $institut->couleur_secondaire }};
        --couleur-accent: {{ $institut->couleur_accent }};
    }
    
    /* Surcharge des couleurs boutique */
    .boutique-header { background: linear-gradient(135deg, var(--couleur-primaire), var(--couleur-secondaire)); }
    .boutique-btn { background: var(--couleur-primaire); }
    .boutique-btn:hover { opacity: 0.9; }
    .boutique-badge { background: var(--couleur-secondaire); }
    .boutique-accent { color: var(--couleur-accent); }
    .boutique-border { border-color: var(--couleur-primaire); }
</style>
@endif
```

### 7.2 Fichiers boutique à modifier

| Fichier | Éléments à adapter |
|---|---|
| `boutique/layouts/app.blade.php` | Variables CSS + classes `.boutique-*` |
| `boutique/index.blade.php` | Boutons, badges, bordures |
| `boutique/produit.blade.php` | Bouton "Ajouter au panier", prix promo |
| `boutique/commander.blade.php` | Bouton "Confirmer", étapes checkout |

### 7.3 Exemple : classe `.boutique-btn`

```blade
{{-- Avant --}}
<button class="w-full py-4 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-bold">

{{-- Après --}}
<button class="w-full py-4 boutique-btn text-white rounded-xl font-bold transition">
```

---

## 8. Dashboard (interface admin)

### 8.1 Approche conservative

Ne **pas** modifier les couleurs du dashboard. L'interface admin reste en violet Maëlya (`primary-600`). Seuls les éléments **orientés client** (PDF, emails, boutique) sont personnalisés.

### 8.2 Si personnalisation dashboard souhaitée

```blade
{{-- Dans layouts/dashboard.blade.php --}}
<style>
    :root {
        --couleur-primaire: {{ $institut->couleur_primaire ?? '#7c3aed' }};
    }
</style>
```

⚠️ Attention : cela affecte **tous** les composants Tailwind `primary-*`. À éviter sur la V1.

---

## 9. Contraste & accessibilité

### 9.1 Vérification automatique

```php
// app/Helpers/CouleurHelper.php

public static function ratioContraste(string $hex1, string $hex2): float
{
    $l1 = self::luminanceRelative($hex1);
    $l2 = self::luminanceRelative($hex2);
    $clair = max($l1, $l2);
    $fonce = min($l1, $l2);
    return ($clair + 0.05) / ($fonce + 0.05);
}

public static function estAccessible(string $fond, string $texte = '#ffffff'): bool
{
    return self::ratioContraste($fond, $texte) >= 4.5; // WCAG AA
}
```

### 9.2 Message d'avertissement dans l'interface

```blade
@if(!\App\Helpers\CouleurHelper::estAccessible($institut->couleur_primaire))
<div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-sm text-amber-700">
    ⚠️ Le contraste entre la couleur principale et le texte blanc est insuffisant.
    Le texte pourrait être difficile à lire. Choisissez une couleur plus foncée.
</div>
@endif
```

---

## 10. Checklist de déploiement

### Phase 1 — DB + Modèle (30 min)

- [ ] Migration exécutée
- [ ] `$fillable` mis à jour sur `Institut`
- [ ] Helper `CouleurHelper` créé

### Phase 2 — Interface admin (1h)

- [ ] Route `parametres/apparence`
- [ ] Vue `apparence.blade.php` avec color pickers + aperçu
- [ ] Lien dans la sidebar "Paramètres → Apparence"
- [ ] Contrôleur `ApparenceController` (edit, update, reset)
- [ ] Validation hexadécimale (`regex:/^#[0-9a-fA-F]{6}$/`)

### Phase 3 — Factures PDF (1h)

- [ ] 8 fichiers PDF modifiés avec `$institut->couleur_primaire`

### Phase 4 — Emails (30 min)

- [ ] 5 templates email modifiés

### Phase 5 — Boutique en ligne (1h30)

- [ ] Variables CSS dans le layout public
- [ ] Classes `.boutique-*` ajoutées
- [ ] 4 vues boutique mises à jour

### Phase 6 — Tests (1h)

- [ ] Couleurs par défaut fonctionnelles
- [ ] Changement de couleurs → PDF immédiat
- [ ] Changement de couleurs → boutique immédiate
- [ ] Contraste averti si < 4.5:1
- [ ] Réinitialisation aux défauts
- [ ] Dark mode non affecté

---

### Temps total estimé : **4-5 heures**
