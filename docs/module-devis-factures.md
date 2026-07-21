# Module Devis & Factures — Plan d'Implémentation

**Date :** 21 juillet 2026  
**Projet :** Maëlya Gestion  
**Version cible :** v1.0 du module  

---

## 📋 Table des matières

1. [Analyse et améliorations](#1-analyse-et-améliorations)
2. [Architecture de la base de données](#2-architecture-de-la-base-de-données)
3. [Modèles et relations](#3-modèles-et-relations)
4. [Migrations](#4-migrations)
5. [Routes](#5-routes)
6. [Contrôleurs](#6-contrôleurs)
7. [Vues et interfaces](#7-vues-et-interfaces)
8. [Génération PDF](#8-génération-pdf)
9. [Portail client](#9-portail-client)
10. [Notifications et emails](#10-notifications-et-emails)
11. [Intégration avec les modules existants](#11-intégration-avec-les-modules-existants)
12. [Tableau de bord et KPIs](#12-tableau-de-bord-et-kpis)
13. [Rappels automatiques](#13-rappels-automatiques)
14. [Plan de déploiement](#14-plan-de-déploiement)

---

## 1. Analyse et améliorations

### 1.1 Cycle de vie complet

```
┌─────────────────────────────────────────────────────────────────┐
│                        CYCLE COMMERCIAL                          │
│                                                                  │
│  PROSPECT ──→ DEVIS ──→ Accepté ──→ FACTURE ──→ Payée ──→ VENTE │
│                │  │                  │                           │
│                │  ├── Refusé         ├── Non payée               │
│                │  ├── Expiré         ├── Partiellement payée     │
│                │  └── En attente     └── Annulée                │
│                │                                                 │
│                └── Transformé en facture (automatique)           │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 1.2 Améliorations proposées par rapport à l'analyse initiale

#### A. Liaison Devis → Vente existante

Quand un devis accepté est transformé en facture puis payé, une **vente** est automatiquement créée dans le module Caisse existant. Cela permet :

- Mise à jour automatique du stock (produits)
- Intégration au chiffre d'affaires
- Historique unifié dans la fiche client
- Cohérence avec les rapports financiers existants

#### B. Gestion de la TVA

Ajout d'un champ `tva` sur les établissements (optionnel, par défaut 0%) :

- TVA applicable par défaut sur les lignes
- Possibilité de surcharger la TVA par ligne
- Calcul automatique du TTC

#### C. Remises hiérarchiques

```
Ligne (remise unitaire)
    ↓
Devis/Facture (remise globale en % ou montant)
    ↓
Total après remises → TVA → TTC
```

#### D. Modèles de devis/facture

- 2-3 templates PDF prédéfinis (classique, moderne, minimal)
- L'établissement choisit son template dans la configuration
- Personnalisation : logo, couleurs, mentions légales

#### E. Portail client enrichi

Au-delà de la consultation, le client pourra :

- Accepter/refuser un devis **avec signature électronique** (dessin ou texte)
- Voir l'historique de ses devis et factures
- Télécharger les PDF
- Payer en ligne (v2 — Orange Money, Mobile Money)

#### F. Relances automatisées (via tâches planifiées)

| Événement | Déclencheur | Action |
|-----------|------------|--------|
| Devis expirant | J-3 avant expiration | Email de rappel au client |
| Facture échue | Jour J | Email de relance |
| Facture en retard | J+7 | Email de relance |
| Facture très en retard | J+30 | Email de mise en demeure |

#### G. Statistiques et filtres avancés

- Filtrer par client, statut, date, commercial
- Export CSV/Excel des devis et factures
- Graphique : évolution du CA devis/factures par mois

---

## 2. Architecture de la base de données

### 2.1 Nouvelles tables

#### `devis` — Devis

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | UUID | PK |
| `institut_id` | UUID FK | Établissement |
| `client_id` | UUID FK nullable | Client (optionnel au brouillon) |
| `user_id` | UUID FK | Créateur |
| `commercial_id` | UUID FK nullable | Commercial |
| `numero` | VARCHAR(30) UNIQUE | DEV-YYYYMM-XXXXXX |
| `statut` | VARCHAR(20) | brouillon, envoye, accepte, refuse, expire |
| `date_creation` | DATE | Date du devis |
| `date_expiration` | DATE | Date d'expiration |
| `date_acceptation` | DATETIME nullable | Date d'acceptation |
| `signature_client` | TEXT nullable | Signature (base64 ou texte) |
| `client_prenom` | VARCHAR(100) | Si client non enregistré |
| `client_nom` | VARCHAR(100) | Si client non enregistré |
| `client_email` | VARCHAR(255) nullable | |
| `client_telephone` | VARCHAR(30) nullable | |
| `client_adresse` | TEXT nullable | |
| `sous_total` | INTEGER | Somme lignes HT |
| `remise_globale_type` | VARCHAR(20) nullable | pourcentage / montant_fixe |
| `remise_globale_valeur` | INTEGER default 0 | |
| `total_ht` | INTEGER | Après remise |
| `tva_applicable` | BOOLEAN default false | |
| `tva_taux` | DECIMAL(5,2) default 0 | |
| `total_ttc` | INTEGER | TTC |
| `notes` | TEXT nullable | |
| `conditions` | TEXT nullable | Conditions générales |
| `facture_id` | UUID FK nullable | Facture liée (si transformé) |
| `timestamps` | | |

#### `devis_items` — Lignes de devis

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | UUID | PK |
| `devis_id` | UUID FK | |
| `produit_id` | UUID FK nullable | |
| `prestation_id` | UUID FK nullable | |
| `designation` | VARCHAR(255) | Libellé |
| `quantite` | INTEGER | |
| `prix_unitaire` | INTEGER | HT |
| `remise_type` | VARCHAR(20) nullable | pourcentage / montant_fixe |
| `remise_valeur` | INTEGER default 0 | |
| `tva_taux` | DECIMAL(5,2) nullable | Surcharge TVA par ligne |
| `total_ligne` | INTEGER | (prix - remise) × qté |
| `ordre` | INTEGER | Tri |
| `timestamps` | | |

#### `factures` — Factures

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | UUID | PK |
| `institut_id` | UUID FK | |
| `client_id` | UUID FK nullable | |
| `devis_id` | UUID FK nullable | Devis d'origine |
| `vente_id` | UUID FK nullable | Vente liée (si payée) |
| `user_id` | UUID FK | Créateur |
| `numero` | VARCHAR(30) UNIQUE | FAC-YYYYMM-XXXXXX |
| `statut` | VARCHAR(20) | brouillon, en_attente, partiellement_payee, payee, annulee |
| `date_emission` | DATE | |
| `date_echeance` | DATE | |
| `client_prenom` | VARCHAR(100) | |
| `client_nom` | VARCHAR(100) | |
| `client_email` | VARCHAR(255) nullable | |
| `client_telephone` | VARCHAR(30) nullable | |
| `client_adresse` | TEXT nullable | |
| `sous_total` | INTEGER | |
| `remise_globale_type` | VARCHAR(20) nullable | |
| `remise_globale_valeur` | INTEGER default 0 | |
| `total_ht` | INTEGER | |
| `tva_applicable` | BOOLEAN default false | |
| `tva_taux` | DECIMAL(5,2) default 0 | |
| `total_ttc` | INTEGER | |
| `montant_paye` | INTEGER default 0 | |
| `notes` | TEXT nullable | |
| `conditions` | TEXT nullable | |
| `timestamps` | | |

#### `facture_items` — Lignes de facture

Mêmes colonnes que `devis_items`, avec `facture_id` au lieu de `devis_id`.

#### `paiements` — Paiements de facture

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | UUID | PK |
| `facture_id` | UUID FK | |
| `user_id` | UUID FK | Qui a encaissé |
| `montant` | INTEGER | |
| `mode_paiement` | VARCHAR(30) | cash, mobile_money, carte, virement |
| `reference` | VARCHAR(100) nullable | |
| `date_paiement` | DATE | |
| `notes` | TEXT nullable | |
| `timestamps` | | |

### 2.2 Modifications de tables existantes

#### `instituts`

```
tva_taux          DECIMAL(5,2) default 0
tva_applicable    BOOLEAN default false
rccm              VARCHAR(50) nullable     — Registre de commerce
numero_fiscal     VARCHAR(50) nullable     — Numéro fiscal
pdf_template      VARCHAR(30) default 'classique'
```

---

## 3. Modèles et relations

### 3.1 Devis

```php
class Devis extends Model
{
    use HasUuids, BelongsToInstitut;

    // Relations
    public function client(): BelongsTo { ... }
    public function createur(): BelongsTo { ... }       // user_id
    public function commercial(): BelongsTo { ... }
    public function items(): HasMany { ... }             // DevisItem
    public function facture(): BelongsTo { ... }         // facture_id

    // Scopes
    public function scopeBrouillons($query)
    public function scopeEnvoyes($query)
    public function scopeAcceptes($query)
    public function scopeExpires($query)
    public function scopeEnCours($query)   // brouillon + envoye
}
```

### 3.2 Facture

```php
class Facture extends Model
{
    use HasUuids, BelongsToInstitut;

    // Relations
    public function client(): BelongsTo { ... }
    public function devis(): BelongsTo { ... }
    public function vente(): BelongsTo { ... }
    public function createur(): BelongsTo { ... }
    public function items(): HasMany { ... }
    public function paiements(): HasMany { ... }

    // Accesseurs
    public function getResteAPayerAttribute(): int
    public function getEstPayeeAttribute(): bool
    public function getEstPartiellementPayeeAttribute(): bool
}
```

---

## 4. Migrations

### 4.1 Ordre des migrations

```
1. 2026_07_21_000001_add_pdf_fields_to_instituts.php
2. 2026_07_21_000002_create_devis_table.php
3. 2026_07_21_000003_create_devis_items_table.php
4. 2026_07_21_000004_create_factures_table.php
5. 2026_07_21_000005_create_facture_items_table.php
6. 2026_07_21_000006_create_paiements_table.php
```

### 4.2 Contenu des migrations

#### Migration 1 : Champs institut

```php
Schema::table('instituts', function (Blueprint $table) {
    $table->decimal('tva_taux', 5, 2)->default(0)->after('boutique_conditions');
    $table->boolean('tva_applicable')->default(false)->after('tva_taux');
    $table->string('rccm', 50)->nullable()->after('tva_applicable');
    $table->string('numero_fiscal', 50)->nullable()->after('rccm');
    $table->string('pdf_template', 30)->default('classique')->after('numero_fiscal');
});
```

#### Migration 2 : Table devis

```php
Schema::create('devis', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('institut_id')->constrained('instituts')->cascadeOnDelete();
    $table->foreignUuid('client_id')->nullable()->constrained('clients')->nullOnDelete();
    $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
    $table->foreignUuid('commercial_id')->nullable()->constrained('users')->nullOnDelete();
    $table->string('numero', 30)->unique();
    $table->string('statut', 20)->default('brouillon');
    $table->date('date_creation');
    $table->date('date_expiration');
    $table->dateTime('date_acceptation')->nullable();
    $table->text('signature_client')->nullable();
    // Client anonyme
    $table->string('client_prenom', 100)->nullable();
    $table->string('client_nom', 100)->nullable();
    $table->string('client_email', 255)->nullable();
    $table->string('client_telephone', 30)->nullable();
    $table->text('client_adresse')->nullable();
    // Montants
    $table->integer('sous_total')->default(0);
    $table->string('remise_globale_type', 20)->nullable();
    $table->integer('remise_globale_valeur')->default(0);
    $table->integer('total_ht')->default(0);
    $table->boolean('tva_applicable')->default(false);
    $table->decimal('tva_taux', 5, 2)->default(0);
    $table->integer('total_ttc')->default(0);
    $table->text('notes')->nullable();
    $table->text('conditions')->nullable();
    $table->foreignUuid('facture_id')->nullable()->constrained('factures')->nullOnDelete();
    $table->timestamps();

    $table->index(['institut_id', 'statut']);
    $table->index('date_creation');
});
```

#### Migration 3 : Table devis_items

```php
Schema::create('devis_items', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('devis_id')->constrained('devis')->cascadeOnDelete();
    $table->foreignUuid('produit_id')->nullable()->constrained('produits')->nullOnDelete();
    $table->foreignUuid('prestation_id')->nullable()->constrained('prestations')->nullOnDelete();
    $table->string('designation', 255);
    $table->integer('quantite')->default(1);
    $table->integer('prix_unitaire');
    $table->string('remise_type', 20)->nullable();
    $table->integer('remise_valeur')->default(0);
    $table->decimal('tva_taux', 5, 2)->nullable();
    $table->integer('total_ligne');
    $table->integer('ordre')->default(0);
    $table->timestamps();
});
```

#### Migration 4 : Table factures

```php
Schema::create('factures', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('institut_id')->constrained('instituts')->cascadeOnDelete();
    $table->foreignUuid('client_id')->nullable()->constrained('clients')->nullOnDelete();
    $table->foreignUuid('devis_id')->nullable()->constrained('devis')->nullOnDelete();
    $table->foreignUuid('vente_id')->nullable()->constrained('ventes')->nullOnDelete();
    $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
    $table->string('numero', 30)->unique();
    $table->string('statut', 20)->default('brouillon');
    $table->date('date_emission');
    $table->date('date_echeance');
    // Client anonyme
    $table->string('client_prenom', 100)->nullable();
    $table->string('client_nom', 100)->nullable();
    $table->string('client_email', 255)->nullable();
    $table->string('client_telephone', 30)->nullable();
    $table->text('client_adresse')->nullable();
    // Montants (identiques à devis)
    $table->integer('sous_total')->default(0);
    $table->string('remise_globale_type', 20)->nullable();
    $table->integer('remise_globale_valeur')->default(0);
    $table->integer('total_ht')->default(0);
    $table->boolean('tva_applicable')->default(false);
    $table->decimal('tva_taux', 5, 2)->default(0);
    $table->integer('total_ttc')->default(0);
    $table->integer('montant_paye')->default(0);
    $table->text('notes')->nullable();
    $table->text('conditions')->nullable();
    $table->timestamps();

    $table->index(['institut_id', 'statut']);
    $table->index('date_echeance');
});
```

#### Migration 5 : Table facture_items

Identique à `devis_items` avec `facture_id` au lieu de `devis_id`.

#### Migration 6 : Table paiements

```php
Schema::create('paiements', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('facture_id')->constrained('factures')->cascadeOnDelete();
    $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
    $table->integer('montant');
    $table->string('mode_paiement', 30)->default('cash');
    $table->string('reference', 100)->nullable();
    $table->date('date_paiement');
    $table->text('notes')->nullable();
    $table->timestamps();
});
```

---

## 5. Routes

### 5.1 Routes Dashboard (espace admin/gérant)

```php
// routes/web.php — dans le groupe dashboard

// ─── Devis ────────────────────────────────────────────────────────
Route::prefix('devis')->name('devis.')->group(function () {
    Route::get('/', [DevisController::class, 'index'])->name('index');
    Route::get('/create', [DevisController::class, 'create'])->name('create');
    Route::post('/', [DevisController::class, 'store'])->name('store');
    Route::get('/{devis}', [DevisController::class, 'show'])->name('show');
    Route::get('/{devis}/edit', [DevisController::class, 'edit'])->name('edit');
    Route::put('/{devis}', [DevisController::class, 'update'])->name('update');
    Route::delete('/{devis}', [DevisController::class, 'destroy'])->name('destroy');

    // Actions
    Route::post('/{devis}/envoyer', [DevisController::class, 'envoyer'])->name('envoyer');
    Route::post('/{devis}/transformer', [DevisController::class, 'transformerEnFacture'])->name('transformer');
    Route::get('/{devis}/pdf', [DevisController::class, 'pdf'])->name('pdf');
    Route::get('/{devis}/dupliquer', [DevisController::class, 'dupliquer'])->name('dupliquer');
});

// ─── Factures ─────────────────────────────────────────────────────
Route::prefix('factures')->name('factures.')->group(function () {
    Route::get('/', [FactureController::class, 'index'])->name('index');
    Route::get('/create', [FactureController::class, 'create'])->name('create');
    Route::post('/', [FactureController::class, 'store'])->name('store');
    Route::get('/{facture}', [FactureController::class, 'show'])->name('show');
    Route::get('/{facture}/edit', [FactureController::class, 'edit'])->name('edit');
    Route::put('/{facture}', [FactureController::class, 'update'])->name('update');
    Route::delete('/{facture}', [FactureController::class, 'destroy'])->name('destroy');

    // Actions
    Route::get('/{facture}/pdf', [FactureController::class, 'pdf'])->name('pdf');
    Route::post('/{facture}/envoyer', [FactureController::class, 'envoyer'])->name('envoyer');
    Route::post('/{facture}/payer', [FactureController::class, 'ajouterPaiement'])->name('payer');
    Route::post('/{facture}/marquer-payee', [FactureController::class, 'marquerPayee'])->name('marquer-payee');
    Route::post('/{facture}/annuler', [FactureController::class, 'annuler'])->name('annuler');
});
```

### 5.2 Routes publiques (portail client)

```php
// routes/web.php — hors auth

Route::prefix('p')->name('portail.')->group(function () {
    Route::get('/devis/{token}', [PortailController::class, 'voirDevis'])->name('devis.voir');
    Route::post('/devis/{token}/accepter', [PortailController::class, 'accepterDevis'])->name('devis.accepter');
    Route::post('/devis/{token}/refuser', [PortailController::class, 'refuserDevis'])->name('devis.refuser');
    Route::get('/facture/{token}', [PortailController::class, 'voirFacture'])->name('facture.voir');
    Route::get('/client/{token}', [PortailController::class, 'espaceClient'])->name('client.accueil');
});
```

---

## 6. Contrôleurs

### 6.1 DevisController (`app/Http/Controllers/Dashboard/DevisController.php`)

```php
class DevisController extends Controller
{
    // GET /dashboard/devis — Liste paginée avec filtres
    public function index(Request $request)

    // GET /dashboard/devis/create — Formulaire création
    public function create()

    // POST /dashboard/devis — Enregistrer
    public function store(Request $request)

    // GET /dashboard/devis/{id} — Détail
    public function show(Devis $devis)

    // GET /dashboard/devis/{id}/edit — Formulaire édition
    public function edit(Devis $devis)

    // PUT /dashboard/devis/{id} — Mise à jour
    public function update(Request $request, Devis $devis)

    // DELETE /dashboard/devis/{id}
    public function destroy(Devis $devis)

    // POST /dashboard/devis/{id}/envoyer — Changer statut → envoyé + email
    public function envoyer(Devis $devis)

    // POST /dashboard/devis/{id}/transformer — Créer facture depuis devis
    public function transformerEnFacture(Devis $devis)

    // GET /dashboard/devis/{id}/pdf
    public function pdf(Devis $devis)

    // GET /dashboard/devis/{id}/dupliquer
    public function dupliquer(Devis $devis)

    // ─── Helpers ───────────────────────────────────────────────────
    private function genererNumero(): string   // DEV-YYYYMM-XXXXXX
    private function calculerTotaux(array $items, array $data): array
}
```

### 6.2 FactureController (`app/Http/Controllers/Dashboard/FactureController.php`)

```php
class FactureController extends Controller
{
    // GET /dashboard/factures
    public function index(Request $request)

    // GET /dashboard/factures/create?devis_id=xxx (optionnel)
    public function create()

    // POST /dashboard/factures
    public function store(Request $request)

    // GET /dashboard/factures/{id}
    public function show(Facture $facture)

    // GET /dashboard/factures/{id}/edit
    public function edit(Facture $facture)

    // PUT /dashboard/factures/{id}
    public function update(Request $request, Facture $facture)

    // DELETE /dashboard/factures/{id}
    public function destroy(Facture $facture)

    // GET /dashboard/factures/{id}/pdf
    public function pdf(Facture $facture)

    // POST /dashboard/factures/{id}/envoyer
    public function envoyer(Facture $facture)

    // POST /dashboard/factures/{id}/payer
    public function ajouterPaiement(Request $request, Facture $facture)

    // POST /dashboard/factures/{id}/marquer-payee — Crée la vente associée
    public function marquerPayee(Facture $facture)

    // POST /dashboard/factures/{id}/annuler
    public function annuler(Facture $facture)
}
```

### 6.3 PortailController (`app/Http/Controllers/PortailController.php`)

```php
class PortailController extends Controller
{
    // GET /p/devis/{token} — Page publique de consultation
    public function voirDevis(string $token)

    // POST /p/devis/{token}/accepter — Accepter avec signature
    public function accepterDevis(Request $request, string $token)

    // POST /p/devis/{token}/refuser
    public function refuserDevis(string $token)

    // GET /p/facture/{token}
    public function voirFacture(string $token)

    // GET /p/client/{token} — Espace client (tous ses devis/factures)
    public function espaceClient(string $token)
}
```

### 6.4 Logique métier — Services

Pour éviter des contrôleurs trop volumineux, la logique métier sera extraite dans des services :

```
app/Services/
    DevisService.php      — generationNumero, calculerTotaux, transformerEnFacture
    FactureService.php    — generationNumero, calculerResteAPayer, marquerPayee
    PdfService.php        — generateDevisPdf, generateFacturePdf
    PortailService.php    — genererToken, verifierToken
```

---

## 7. Vues et interfaces

### 7.1 Structure des vues

```
resources/views/dashboard/
├── devis-factures/
│   ├── index.blade.php          ← Page avec 2 onglets (Devis | Factures)
│   ├── devis/
│   │   ├── create.blade.php     ← Formulaire création devis
│   │   ├── edit.blade.php       ← Formulaire édition devis
│   │   └── show.blade.php       ← Détail devis
│   ├── factures/
│   │   ├── create.blade.php     ← Formulaire création facture
│   │   ├── edit.blade.php       ← Formulaire édition facture
│   │   └── show.blade.php       ← Détail facture
│   └── partials/
│       ├── form-lignes.blade.php   ← Composant lignes (réutilisé devis+facture)
│       ├── form-totaux.blade.php   ← Composant totaux
│       └── timeline.blade.php      ← Timeline statuts
│
├── portail/
│   ├── devis.blade.php          ← Vue publique devis
│   ├── facture.blade.php        ← Vue publique facture
│   └── client.blade.php         ← Espace client
│
└── pdf/
    ├── devis-classique.blade.php
    ├── devis-moderne.blade.php
    ├── facture-classique.blade.php
    └── facture-moderne.blade.php
```

### 7.2 Page principale — Onglets Devis / Factures

```
┌──────────────────────────────────────────────────────────┐
│  Devis & Factures                                        │
│                                                          │
│  [Devis (12)]  [Factures (8)]                            │
│  ─────────────────────────────────────────────────────── │
│  [Stats: 3 devis en cours · 450 000 F · Taux: 75%]      │
│                                                          │
│  [🔍 Rechercher...]  [Statut ▼]  [+ Nouveau devis]      │
│                                                          │
│  ┌────────────────────────────────────────────────────┐  │
│  │ N°          Client      Date      Total   Statut   │  │
│  │ DEV-001     M. Koné    21/07    150 000  Envoyé   │  │
│  │ DEV-002     Mme Yass   20/07     80 000  Accepté  │  │
│  └────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────┘
```

### 7.3 Formulaire création/édition — Lignes dynamiques (Alpine.js)

Le formulaire de lignes utilise Alpine.js pour :

- Ajouter/supprimer des lignes dynamiquement
- Rechercher un produit/prestation existant (autocomplétion)
- Saisie libre (désignation manuelle)
- Calcul automatique des totaux (remise, TVA)

```blade
<div x-data="lignesManager()">
    <template x-for="(ligne, i) in lignes" :key="i">
        <div class="flex gap-2">
            <input x-model="ligne.designation" placeholder="Désignation">
            <input type="number" x-model="ligne.quantite" min="1">
            <input type="number" x-model="ligne.prix_unitaire">
            <!-- remise, TVA... -->
            <button @click="lignes.splice(i,1)">✕</button>
        </div>
    </template>
    <button @click="ajouterLigne()">+ Ajouter une ligne</button>

    <!-- Totaux -->
    <div>
        Sous-total : <span x-text="formatNumber(sousTotal)"></span>
        Remise : <input x-model="remiseGlobale">
        Total HT : <span x-text="formatNumber(totalHT)"></span>
        TVA : <span x-text="formatNumber(montantTVA)"></span>
        Total TTC : <span x-text="formatNumber(totalTTC)"></span>
    </div>

    <input type="hidden" name="lignes" :value="JSON.stringify(lignes)">
</div>
```

### 7.4 Vue détail — Show

```
┌──────────────────────────────────────────────────────────┐
│  DEV-2026-000001                          [Brouillon]    │
│  Créé le 21/07/2026 · Expire le 28/07/2026              │
│                                                          │
│  ┌──────────────┐  ┌──────────────────────────────────┐  │
│  │ Client        │  │ Lignes                          │  │
│  │ M. Koné       │  │ Shampoing ×2    5 000 F  10 000 │  │
│  │ 07 XX XX XX   │  │ Coupe ×1        3 000 F   3 000 │  │
│  └──────────────┘  │                      Total 13 000 │  │
│                    └──────────────────────────────────┘  │
│                                                          │
│  [✏️ Modifier] [📄 PDF] [📧 Envoyer] [🔄 Transformer]   │
└──────────────────────────────────────────────────────────┘
```

---

## 8. Génération PDF

### 8.1 Bibliothèque

Utilisation de **Barryvdh/Laravel-Dompdf** (déjà installé).

### 8.2 Templates

Deux templates par défaut :

#### Classique
- En-tête : logo à gauche, infos établissement à droite
- Tableau des lignes avec colonnes : Désignation, Qté, PU, Remise, Total
- Pied de page : conditions, signature

#### Moderne
- Bandeau coloré (couleur primaire de l'établissement)
- Logo centré
- Tableau épuré
- QR code vers le portail client (optionnel)

### 8.3 Structure d'un template PDF

```blade
<!DOCTYPE html>
<html>
<head>
    <style>
        @page { margin: 20mm; }
        .header { ... }
        .lines-table { width: 100%; border-collapse: collapse; }
        .totals { margin-top: 20px; text-align: right; }
        .footer { position: fixed; bottom: 0; }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ $institut->logo_url }}" height="60">
        <div class="company-info">
            <h2>{{ $institut->nom }}</h2>
            <p>{{ $institut->adresse }} · {{ $institut->telephone }}</p>
            @if($institut->rccm) <p>RCCM: {{ $institut->rccm }}</p> @endif
            @if($institut->numero_fiscal) <p>N° Fiscal: {{ $institut->numero_fiscal }}</p> @endif
        </div>
    </div>

    <h1>{{ $type === 'devis' ? 'DEVIS' : 'FACTURE' }} N°{{ $document->numero }}</h1>
    <!-- ... -->
</body>
</html>
```

---

## 9. Portail client

### 9.1 Fonctionnement

Chaque devis/facture reçoit un **token unique** (UUID raccourci) au moment de l'envoi :

```php
$devis->token = Str::random(32);  // Stocké dans une colonne 'token'
```

Le lien envoyé au client : `https://maelyagestion.com/p/devis/{token}`

### 9.2 Pages du portail

#### Consultation devis

```
┌──────────────────────────────────────────────┐
│  Institut Bercail — Devis N° DEV-2026-001    │
│                                              │
│  [Logo]                                      │
│                                              │
│  ┌──────────────────────────────────────┐    │
│  │ Lignes                               │    │
│  │ ...                                  │    │
│  │ Total TTC : 150 000 FCFA             │    │
│  └──────────────────────────────────────┘    │
│                                              │
│  Valable jusqu'au 28/07/2026                 │
│                                              │
│  [📥 Télécharger PDF]                        │
│                                              │
│  ┌─ Signature ──────────────────────────┐    │
│  │ [           Dessinez ici         ]    │    │
│  │ Je soussigné(e) _______________      │    │
│  │ accepte ce devis                     │    │
│  │                                      │    │
│  │ [✅ Accepter le devis]               │    │
│  └──────────────────────────────────────┘    │
└──────────────────────────────────────────────┘
```

#### Signature électronique

Utilisation d'un canvas HTML5 pour le dessin de signature :

```javascript
// Signature Pad simplifié
const canvas = document.getElementById('signature-pad');
const ctx = canvas.getContext('2d');
let drawing = false;

canvas.addEventListener('mousedown', startDrawing);
canvas.addEventListener('mousemove', draw);
canvas.addEventListener('mouseup', stopDrawing);
// + événements tactiles pour mobile

function sauvegarderSignature() {
    const dataUrl = canvas.toDataURL('image/png');
    // Envoyer au serveur
}
```

---

## 10. Notifications et emails

### 10.1 Classes Mail à créer

```
app/Mail/
    DevisEnvoye.php          — Envoyé au client
    DevisAccepte.php         — Notification à l'établissement
    FactureEnvoyee.php       — Envoyé au client
    FacturePayee.php         — Notification à l'établissement
    RappelDevisExpiration.php
    RappelFactureEcheance.php
```

### 10.2 Templates email

Les emails suivent le **thème Maëlya Gestion** (dégradé violet, footer établissement) déjà utilisé pour les emails de commande.

### 10.3 Tâches planifiées (rappels automatiques)

```php
// app/Console/Kernel.php
$schedule->command('devis:rappel-expiration')->dailyAt('08:00');
$schedule->command('factures:rappel-echeance')->dailyAt('08:00');
$schedule->command('factures:rappel-retard')->dailyAt('08:00');
```

#### Commandes Artisan à créer

```
app/Console/Commands/
    RappelDevisExpiration.php     — J-3, J-1
    RappelFactureEcheance.php     — Jour J, J+7, J+30
```

---

## 11. Intégration avec les modules existants

### 11.1 Transformation devis → facture

```php
// DevisService.php
public function transformerEnFacture(Devis $devis): Facture
{
    DB::transaction(function () use ($devis) {
        $facture = Facture::create([
            'institut_id' => $devis->institut_id,
            'devis_id' => $devis->id,
            'client_id' => $devis->client_id,
            'user_id' => auth()->id(),
            'numero' => FactureService::genererNumero(),
            // ... copier les données du devis
        ]);

        foreach ($devis->items as $item) {
            FactureItem::create([...]); // Copier chaque ligne
        }

        $devis->update(['facture_id' => $facture->id]);

        return $facture;
    });
}
```

### 11.2 Paiement facture → création vente

Quand une facture est marquée comme payée (`marquerPayee`), une **vente** est créée automatiquement :

```php
// FactureService.php
public function marquerPayee(Facture $facture): Vente
{
    DB::transaction(function () use ($facture) {
        $vente = Vente::create([
            'institut_id' => $facture->institut_id,
            'client_id' => $facture->client_id,
            'user_id' => auth()->id(),
            'total' => $facture->total_ttc,
            'montant_paye' => $facture->total_ttc,
            'mode_paiement' => 'cash', // ou mixte selon les paiements
            'statut' => 'validee',
        ]);

        foreach ($facture->items as $item) {
            VenteItem::create([...]);
            // Décrémenter le stock si produit
        }

        $facture->update([
            'statut' => 'payee',
            'montant_paye' => $facture->total_ttc,
            'vente_id' => $vente->id,
        ]);

        return $vente;
    });
}
```

### 11.3 Intégration dans la fiche client

Dans `ClientController@show`, ajouter un onglet ou une section :

```blade
{{-- Fiche client — onglets --}}
[Infos] [Ventes] [Rendez-vous] [Devis] [Factures]
```

---

## 12. Tableau de bord et KPIs

### 12.1 Dashboard Devis & Factures

Accessible depuis le menu principal → **Devis & Factures**.

#### Onglet Devis

| KPI | Calcul |
|-----|--------|
| Devis en cours | COUNT où statut IN (brouillon, envoye) |
| Montant total devis | SUM(total_ttc) des devis en cours |
| Taux d'acceptation | (nb_acceptes / nb_envoyes) × 100 |
| Devis expirés ce mois | COUNT expirés sur les 30 derniers jours |

#### Onglet Factures

| KPI | Calcul |
|-----|--------|
| Total facturé | SUM(total_ttc) |
| Total encaissé | SUM(montant_paye) |
| Restant dû | total_facturé - total_encaissé |
| Factures en retard | COUNT où date_echeance < today AND statut != payee |

### 12.2 Dashboard principal (optionnel)

Ajouter une carte "Derniers devis" dans le dashboard principal, visible uniquement si l'établissement utilise le module.

---

## 13. Rappels automatiques

### 13.1 Architecture

Les rappels sont gérés par des **commandes Artisan** exécutées via le scheduler Laravel.

#### Flux d'un rappel

```
Scheduler (daily 08:00)
    │
    ▼
Commande Artisan (ex: RappelDevisExpiration)
    │
    ├── Récupère les devis expirant dans 3 jours
    ├── Pour chaque devis :
    │   ├── Vérifie si le client a un email
    │   ├── Envoie l'email via Brevo SMTP
    │   └── Log l'envoi
    │
    └── Rapport : X emails envoyés
```

### 13.2 Exemple : `RappelFactureEcheance`

```php
class RappelFactureEcheance extends Command
{
    protected $signature = 'factures:rappel-echeance {--jours=0}';

    public function handle()
    {
        $jours = (int) $this->option('jours');
        $dateCible = now()->addDays($jours)->toDateString();

        $factures = Facture::where('statut', '!=', 'payee')
            ->whereDate('date_echeance', $dateCible)
            ->whereNotNull('client_email')
            ->get();

        foreach ($factures as $facture) {
            Mail::to($facture->client_email)
                ->send(new RappelFactureEcheance($facture, $jours));
        }

        $this->info("{$factures->count()} rappels envoyés.");
    }
}
```

### 13.3 Planification dans le Kernel

```php
// J-3 devis
$schedule->command('devis:rappel-expiration --jours=3')->dailyAt('08:00');
// J-1 devis
$schedule->command('devis:rappel-expiration --jours=1')->dailyAt('08:00');
// Jour J facture
$schedule->command('factures:rappel-echeance --jours=0')->dailyAt('08:00');
// J+7 facture
$schedule->command('factures:rappel-echeance --jours=-7')->dailyAt('08:00');
// J+30 facture
$schedule->command('factures:rappel-echeance --jours=-30')->dailyAt('08:00');
```

---

## 14. Plan de déploiement

### 14.1 Phase 1 — Base (Sprint 1-2)

| Tâche | Durée |
|-------|-------|
| Migrations (6 migrations) | 1 jour |
| Modèles + relations + scopes | 0.5 jour |
| DevisController (CRUD) | 1.5 jours |
| Vues devis (liste, création, détail) | 2 jours |
| Formulaire lignes dynamiques (Alpine.js) | 1 jour |
| Service numérotation + calcul totaux | 0.5 jour |
| **Sous-total** | **6.5 jours** |

### 14.2 Phase 2 — Factures (Sprint 3-4)

| Tâche | Durée |
|-------|-------|
| FactureController (CRUD) | 1.5 jours |
| Vues factures | 1 jour |
| Transformation devis → facture | 0.5 jour |
| Paiements multiples | 1 jour |
| Paiement → création vente | 0.5 jour |
| **Sous-total** | **4.5 jours** |

### 14.3 Phase 3 — PDF & Portail (Sprint 5-6)

| Tâche | Durée |
|-------|-------|
| Templates PDF (2 modèles) | 1.5 jours |
| PortailController (routes publiques) | 0.5 jour |
| Vue publique devis + signature | 1.5 jours |
| Vue publique facture | 0.5 jour |
| Espace client | 1 jour |
| **Sous-total** | **5 jours** |

### 14.4 Phase 4 — Emails & Rappels (Sprint 7)

| Tâche | Durée |
|-------|-------|
| Classes Mail (6 templates) | 1 jour |
| Commandes Artisan (2 commandes) | 1 jour |
| Planification scheduler | 0.5 jour |
| Tests et recette | 1 jour |
| **Sous-total** | **3.5 jours** |

### 14.5 Phase 5 — Intégration & Polish (Sprint 8)

| Tâche | Durée |
|-------|-------|
| Stats dashboard | 0.5 jour |
| Filtres avancés | 0.5 jour |
| Export CSV/Excel | 0.5 jour |
| Tests complets | 1 jour |
| Documentation | 0.5 jour |
| **Sous-total** | **3 jours** |

### 14.6 Total estimé

| Phase | Jours |
|-------|-------|
| Base (Devis) | 6.5 |
| Factures | 4.5 |
| PDF & Portail | 5 |
| Emails & Rappels | 3.5 |
| Intégration & Polish | 3 |
| **Total** | **~22.5 jours** |

---

## ✅ Prochaines étapes

1. **Validation** du plan par le client
2. **Création des migrations** (Phase 1)
3. **Développement itératif** par phase
4. **Tests** sur environnement de dev avant chaque mise en production

---

*Document rédigé le 21 juillet 2026 — Projet Maëlya Gestion*
