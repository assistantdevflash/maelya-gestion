# 📌 Module Crédits Clients & Échéanciers — Guide d'implémentation

> **Date** : 26 juin 2026  
> **Statut** : 🔴 Non démarré  
> **Dernière mise à jour** : —

---

## 🎯 Objectif

Permettre aux établissements de proposer des ventes à crédit avec suivi des échéances, encaissements et relances.

> Le client reçoit l'article immédiatement et s'engage à payer le solde selon un calendrier défini.

---

## 📋 Phases d'implémentation

| Phase | Contenu | Statut |
|-------|---------|--------|
| **Phase 1** | Migrations + Modèles Eloquent | ⬜ À faire |
| **Phase 2** | Interface caisse — mode Crédit | ⬜ À faire |
| **Phase 3** | Page `/dashboard/credits` — suivi | ⬜ À faire |
| **Phase 4** | Encaissement échéances | ⬜ À faire |
| **Phase 5** | Détection retards + relances | ⬜ À faire |

---

## Phase 1 — Base de données & Modèles

### 1a. Modification table `ventes`

```sql
-- Ajouter 'credit' à l'enum mode_paiement
ALTER TABLE ventes 
MODIFY COLUMN mode_paiement 
ENUM('cash','mobile_money','mixte','credit') DEFAULT 'cash';

-- Ajouter les colonnes de suivi crédit
ALTER TABLE ventes 
ADD COLUMN montant_paye DECIMAL(10,0) DEFAULT 0 AFTER total;

ALTER TABLE ventes 
ADD COLUMN credit_statut 
ENUM('en_cours','solde','retard','defaut') NULL AFTER statut;
```

### 1b. Création table `credits`

```sql
CREATE TABLE credits (
    id CHAR(36) PRIMARY KEY,
    institut_id CHAR(36) NOT NULL,
    vente_id CHAR(36) NOT NULL UNIQUE,
    client_id CHAR(36) NOT NULL,
    
    montant_total DECIMAL(10,0) NOT NULL,
    apport_initial DECIMAL(10,0) NOT NULL DEFAULT 0,
    reste_a_payer DECIMAL(10,0) NOT NULL,
    
    nb_echeances INT NOT NULL DEFAULT 1,
    frequence ENUM('hebdomadaire','mensuelle') DEFAULT 'mensuelle',
    
    statut ENUM('en_cours','solde','retard','defaut') DEFAULT 'en_cours',
    
    date_debut DATE NOT NULL,
    date_fin_prevue DATE NULL,
    notes TEXT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (vente_id) REFERENCES ventes(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id),
    FOREIGN KEY (institut_id) REFERENCES instituts(id) ON DELETE CASCADE,
    
    INDEX idx_credit_institut_statut (institut_id, statut),
    INDEX idx_credit_client (client_id),
    INDEX idx_credit_vente (vente_id)
);
```

### 1c. Création table `echeances`

```sql
CREATE TABLE echeances (
    id CHAR(36) PRIMARY KEY,
    credit_id CHAR(36) NOT NULL,
    institut_id CHAR(36) NOT NULL,
    
    numero INT NOT NULL,
    date_prevue DATE NOT NULL,
    montant DECIMAL(10,0) NOT NULL,
    montant_paye DECIMAL(10,0) DEFAULT 0,
    
    date_paiement DATE NULL,
    encaisse_par CHAR(36) NULL,
    
    statut ENUM('en_attente','payee','retard','annulee') DEFAULT 'en_attente',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (credit_id) REFERENCES credits(id) ON DELETE CASCADE,
    FOREIGN KEY (institut_id) REFERENCES instituts(id) ON DELETE CASCADE,
    FOREIGN KEY (encaisse_par) REFERENCES users(id),
    
    INDEX idx_echeance_date (date_prevue, statut),
    INDEX idx_echeance_credit (credit_id)
);
```

### 1d. Création table `paiements_credit`

```sql
CREATE TABLE paiements_credit (
    id CHAR(36) PRIMARY KEY,
    credit_id CHAR(36) NOT NULL,
    echeance_id CHAR(36) NULL,
    institut_id CHAR(36) NOT NULL,
    
    montant DECIMAL(10,0) NOT NULL,
    mode_paiement ENUM('cash','mobile_money','carte') NOT NULL,
    reference VARCHAR(100) NULL,
    
    encaisse_par CHAR(36) NOT NULL,
    notes TEXT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (credit_id) REFERENCES credits(id) ON DELETE CASCADE,
    FOREIGN KEY (echeance_id) REFERENCES echeances(id) ON DELETE SET NULL,
    FOREIGN KEY (institut_id) REFERENCES instituts(id) ON DELETE CASCADE,
    FOREIGN KEY (encaisse_par) REFERENCES users(id),
    
    INDEX idx_paiement_credit (credit_id)
);
```

### 1e. Modèles Eloquent

**Fichier** : `app/Models/Credit.php`

```php
class Credit extends Model
{
    use HasUuids, BelongsToInstitut;

    protected $fillable = [
        'vente_id', 'client_id', 'institut_id',
        'montant_total', 'apport_initial', 'reste_a_payer',
        'nb_echeances', 'frequence', 'statut',
        'date_debut', 'date_fin_prevue', 'notes',
    ];

    protected $casts = [
        'montant_total'   => 'integer',
        'apport_initial'  => 'integer',
        'reste_a_payer'   => 'integer',
        'date_debut'      => 'date',
        'date_fin_prevue' => 'date',
    ];

    public function vente()      { return $this->belongsTo(Vente::class); }
    public function client()     { return $this->belongsTo(Client::class); }
    public function echeances()  { return $this->hasMany(Echeance::class)->orderBy('numero'); }
    public function paiements()  { return $this->hasMany(PaiementCredit::class)->latest(); }
}
```

**Fichier** : `app/Models/Echeance.php`

```php
class Echeance extends Model
{
    use HasUuids, BelongsToInstitut;

    protected $fillable = [
        'credit_id', 'institut_id', 'numero',
        'date_prevue', 'montant', 'montant_paye',
        'date_paiement', 'encaisse_par', 'statut',
    ];

    protected $casts = [
        'montant'       => 'integer',
        'montant_paye'  => 'integer',
        'date_prevue'   => 'date',
        'date_paiement' => 'date',
    ];

    public function credit() { return $this->belongsTo(Credit::class); }
    public function encaisseur() { return $this->belongsTo(User::class, 'encaisse_par'); }
}
```

**Fichier** : `app/Models/PaiementCredit.php`

```php
class PaiementCredit extends Model
{
    use HasUuids, BelongsToInstitut;

    protected $fillable = [
        'credit_id', 'echeance_id', 'institut_id',
        'montant', 'mode_paiement', 'reference',
        'encaisse_par', 'notes',
    ];

    protected $casts = ['montant' => 'integer'];

    public function credit() { return $this->belongsTo(Credit::class); }
    public function echeance() { return $this->belongsTo(Echeance::class); }
    public function encaisseur() { return $this->belongsTo(User::class, 'encaisse_par'); }
}
```

**Mise à jour `app/Models/Vente.php`** :

```php
// Ajouter dans $fillable :
'montant_paye', 'credit_statut',

// Ajouter dans $casts :
'montant_paye' => 'integer',

// Ajouter la relation :
public function credit()
{
    return $this->hasOne(Credit::class);
}
```

**Mise à jour `app/Models/Client.php`** :

```php
// Ajouter la relation :
public function credits()
{
    return $this->hasMany(Credit::class);
}

// Ajouter un accesseur pour le score client :
public function getScoreCreditAttribute(): int
{
    $credits = $this->credits;
    if ($credits->isEmpty()) return 0;
    
    $total = $credits->count();
    $retards = $credits->where('statut', 'retard')->count();
    $soldes = $credits->where('statut', 'solde')->count();
    $ratio = $total > 0 ? ($total - $retards) / $total : 0;
    
    return max(1, min(5, (int) round($ratio * 5)));
}
```

---

## Phase 2 — Interface Caisse (mode Crédit)

### 2a. Composant Livewire `Caisse.php`

Ajouter les propriétés :

```php
public int $apportInitial = 0;
public int $nbEcheances = 3;
public string $frequenceCredit = 'mensuelle';
```

Ajouter la méthode `validerVenteCredit()` :

```php
public function validerVenteCredit(
    int $apportInitial,
    int $nbEcheances,
    string $frequenceCredit,
    array $panier,
    // ... autres paramètres
)
{
    // 1. Validation
    if (!$this->clientId) {
        $this->addError('credit', 'Un client est obligatoire pour une vente à crédit.');
        return;
    }
    if ($apportInitial < 0) {
        $this->addError('credit', "L'apport initial ne peut pas être négatif.");
        return;
    }

    // 2. Créer la vente (mode crédit)
    $total = array_sum(array_map(fn($i) => $i['prix'] * $i['quantite'], $panier));
    $reste = max(0, $total - $apportInitial);

    $vente = Vente::create([
        'institut_id'     => $this->institutId(),
        'client_id'       => $this->clientId,
        'user_id'         => Auth::id(),
        'total'           => $total,
        'montant_paye'    => $apportInitial,
        'mode_paiement'   => 'credit',
        'credit_statut'   => 'en_cours',
        'statut'          => 'validee',
        'numero'          => 'V-' . strtoupper(Str::random(8)),
        'ip_address'      => request()->ip(),
    ]);

    // 3. Enregistrer les items
    foreach ($panier as $item) {
        $vente->items()->create([
            'type'          => $item['type'],
            'item_id'       => $item['id'],
            'nom_snapshot'  => $item['nom'],
            'prix_snapshot' => $item['prix'],
            'quantite'      => $item['quantite'],
            'sous_total'    => $item['prix'] * $item['quantite'],
        ]);
    }

    // 4. Créer le crédit
    $credit = Credit::create([
        'vente_id'        => $vente->id,
        'institut_id'     => $this->institutId(),
        'client_id'       => $this->clientId,
        'montant_total'   => $total,
        'apport_initial'  => $apportInitial,
        'reste_a_payer'   => $reste,
        'nb_echeances'    => $nbEcheances,
        'frequence'       => $frequenceCredit,
        'date_debut'      => now(),
        'date_fin_prevue' => $this->calculerDateFin(now(), $nbEcheances, $frequenceCredit),
        'statut'          => 'en_cours',
    ]);

    // 5. Générer les échéances
    $parEcheance = (int) round($reste / $nbEcheances);
    $date = now();
    $cumul = 0;
    for ($i = 0; $i < $nbEcheances; $i++) {
        $date = $this->ajouterPeriode($date, $frequenceCredit);
        $cumul += $parEcheance;
        $montant = ($i === $nbEcheances - 1)
            ? $reste - ($parEcheance * ($nbEcheances - 1))
            : $parEcheance;

        Echeance::create([
            'credit_id'   => $credit->id,
            'institut_id' => $this->institutId(),
            'numero'      => $i + 1,
            'date_prevue' => $date->toDateString(),
            'montant'     => max(0, $montant),
            'statut'      => 'en_attente',
        ]);
    }

    // 6. Enregistrer l'apport comme premier paiement
    if ($apportInitial > 0) {
        PaiementCredit::create([
            'credit_id'     => $credit->id,
            'institut_id'   => $this->institutId(),
            'montant'       => $apportInitial,
            'mode_paiement' => 'cash', // défaut, à rendre paramétrable
            'encaisse_par'  => Auth::id(),
            'notes'         => 'Apport initial',
        ]);
    }

    // 7. Réinitialiser et rediriger
    $this->resetCaisse();
    session()->flash('success', 'Vente à crédit enregistrée — reste : ' . number_format($reste) . ' FCFA');
    return redirect()->route('dashboard.credits.index');
}

private function calculerDateFin($date, int $nbEcheances, string $frequence): string
{
    $d = clone $date;
    for ($i = 0; $i < $nbEcheances; $i++) {
        $d = $this->ajouterPeriode($d, $frequence);
    }
    return $d->toDateString();
}

private function ajouterPeriode($date, string $frequence): Carbon
{
    $d = $date instanceof Carbon ? clone $date : Carbon::parse($date);
    return $frequence === 'hebdomadaire' ? $d->addWeek() : $d->addMonth();
}
```

### 2b. Vue `caisse.blade.php`

Ajouter le 5ᵉ bouton de paiement :

```blade
{{-- Bouton Crédit --}}
<button @click="modePaiement = 'credit'"
        :class="modePaiement === 'credit' ? 'border-emerald-500 bg-emerald-50 text-emerald-700 shadow-sm' : 'border-gray-200 text-gray-500 hover:border-gray-300'"
        class="flex-1 py-2.5 border-2 rounded-xl text-sm font-semibold transition-all duration-200 flex items-center justify-center gap-1">
    🕐
    Crédit
</button>
```

Ajouter le panneau crédit quand le mode est sélectionné :

```blade
{{-- Panneau Crédit --}}
<template x-if="modePaiement === 'credit'">
    <div class="space-y-3 p-4 bg-emerald-50/50 rounded-xl border border-emerald-200">
        {{-- Client — doit être sélectionné --}}
        <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-gray-700">Client</span>
            @if($clientId && $selectedClient)
                <span class="text-sm font-bold text-emerald-700">{{ $selectedClient->nom_complet }}</span>
            @else
                <span class="text-sm text-red-500">⚠️ Client obligatoire pour un crédit</span>
            @endif
        </div>

        {{-- Apport initial --}}
        <div>
            <label class="text-xs text-gray-500">Apport initial (optionnel)</label>
            <input type="number" x-model.number="apportInitial" min="0"
                   class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm">
        </div>

        {{-- Résumé --}}
        <div class="bg-white rounded-lg p-3 text-sm space-y-1">
            <div class="flex justify-between">
                <span>Total vente</span>
                <strong x-text="total + ' FCFA'"></strong>
            </div>
            <div class="flex justify-between text-emerald-600">
                <span>Apport</span>
                <strong x-text="apportInitial + ' FCFA'"></strong>
            </div>
            <div class="flex justify-between text-red-600 font-bold pt-1 border-t">
                <span>Reste à payer</span>
                <strong x-text="(total - apportInitial) + ' FCFA'"></strong>
            </div>
        </div>

        {{-- Nombre d'échéances --}}
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="text-xs text-gray-500">Nombre d'échéances</label>
                <select x-model.number="nbEcheances"
                        class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm">
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="6">6</option>
                    <option value="12">12</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500">Fréquence</label>
                <select x-model="frequenceCredit"
                        class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm">
                    <option value="mensuelle">Mensuelle</option>
                    <option value="hebdomadaire">Hebdomadaire</option>
                </select>
            </div>
        </div>

        {{-- Valider --}}
        <button @click="validerVenteCredit()"
                :disabled="!clientId || (total - apportInitial) <= 0"
                class="w-full py-3 rounded-xl text-white font-bold bg-gradient-to-r from-emerald-500 to-teal-600 disabled:opacity-50">
            Enregistrer la vente à crédit
        </button>
    </div>
</template>
```

### 2c. Mise à jour `caisse.js`

Ajouter dans l'objet `caisseApp` :

```js
// Nouvelles propriétés
creditApport: 0,
creditNbEcheances: 3,
creditFrequence: 'mensuelle',

// Nouvelle méthode
validerVenteCredit() {
    if (!this.clientId) {
        alert('Veuillez sélectionner un client.');
        return;
    }
    const reste = this.total - this.creditApport;
    if (reste <= 0) {
        alert("Le reste à payer doit être supérieur à 0.");
        return;
    }
    if (!confirm(`Confirmer la vente à crédit ?\n\nTotal : ${this.total} FCFA\nApport : ${this.creditApport} FCFA\nReste : ${reste} FCFA\n${this.creditNbEcheances} échéances ${this.creditFrequence === 'mensuelle' ? 'mensuelles' : 'hebdomadaires'}`)) {
        return;
    }
    this.$wire.validerVenteCredit(
        this.creditApport,
        this.creditNbEcheances,
        this.creditFrequence,
        Object.values(this.panier),
        this.codePromo?.id || null
    );
}
```

---

## Phase 3 — Page `/dashboard/credits`

### 3a. Routes

```php
// routes/web.php
Route::middleware(['auth', 'verified', 'abonnement.actif'])->group(function () {
    Route::get('credits', [CreditController::class, 'index'])->name('credits.index');
    Route::get('credits/{credit}', [CreditController::class, 'show'])->name('credits.show');
    Route::post('credits/{credit}/payer', [CreditController::class, 'payer'])->name('credits.payer');
    Route::post('credits/{credit}/relance', [CreditController::class, 'relance'])->name('credits.relance');
});
```

### 3b. Contrôleur `CreditController`

```php
class CreditController extends Controller
{
    public function index(Request $request)
    {
        $filtre = $request->input('statut', 'tous');
        $search = $request->input('q');

        $credits = Credit::where('institut_id', $this->institutId())
            ->with(['client', 'vente.items'])
            ->when($filtre !== 'tous', fn($q) => $q->where('statut', $filtre))
            ->when($search, fn($q) => $q->whereHas('client', fn($q2) =>
                $q2->where('prenom', 'like', "%{$search}%")
                   ->orWhere('nom', 'like', "%{$search}%")
                   ->orWhere('telephone', 'like', "%{$search}%")
            ))
            ->latest()
            ->paginate(30);

        $totaux = Credit::where('institut_id', $this->institutId())
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN statut = 'en_cours' THEN 1 ELSE 0 END) as en_cours,
                SUM(CASE WHEN statut = 'retard' THEN 1 ELSE 0 END) as en_retard,
                SUM(CASE WHEN statut = 'solde' THEN 1 ELSE 0 END) as soldes,
                COALESCE(SUM(reste_a_payer), 0) as total_du
            ")->first();

        return view('dashboard.credits.index', compact('credits', 'filtre', 'totaux'));
    }

    public function show(Credit $credit)
    {
        $credit->load(['client', 'vente.items', 'echeances', 'paiements.encaisseur']);
        return view('dashboard.credits.show', compact('credit'));
    }

    public function payer(Request $request, Credit $credit)
    {
        // Validation
        $data = $request->validate([
            'echeance_id'    => ['required', 'uuid', 'exists:echeances,id'],
            'montant'        => ['required', 'integer', 'min:1'],
            'mode_paiement'  => ['required', 'in:cash,mobile_money,carte'],
            'reference'      => ['nullable', 'string', 'max:100'],
        ]);

        $echeance = Echeance::findOrFail($data['echeance_id']);
        abort_if($echeance->credit_id !== $credit->id, 403);

        DB::transaction(function () use ($credit, $echeance, $data) {
            // Paiement
            PaiementCredit::create([
                'credit_id'     => $credit->id,
                'echeance_id'   => $echeance->id,
                'institut_id'   => $this->institutId(),
                'montant'       => $data['montant'],
                'mode_paiement' => $data['mode_paiement'],
                'reference'     => $data['reference'],
                'encaisse_par'  => Auth::id(),
            ]);

            // Mettre à jour l'échéance
            $echeance->montant_paye += $data['montant'];
            if ($echeance->montant_paye >= $echeance->montant) {
                $echeance->statut = 'payee';
                $echeance->date_paiement = now();
                $echeance->encaisse_par = Auth::id();
            }
            $echeance->save();

            // Mettre à jour le crédit
            $credit->reste_a_payer = max(0, $credit->reste_a_payer - $data['montant']);
            if ($credit->reste_a_payer <= 0) {
                $credit->statut = 'solde';
                $credit->vente->credit_statut = 'solde';
                $credit->vente->save();
            } elseif ($credit->statut === 'retard') {
                // Vérifier s'il reste des échéances en retard
                $aDesRetards = $credit->echeances()
                    ->where('statut', 'retard')
                    ->where('date_prevue', '<', now())
                    ->exists();
                if (!$aDesRetards) {
                    $credit->statut = 'en_cours';
                    $credit->vente->credit_statut = 'en_cours';
                    $credit->vente->save();
                }
            }
            $credit->vente->montant_paye += $data['montant'];
            $credit->vente->save();
            $credit->save();
        });

        return back()->with('success', 'Paiement de ' . number_format($data['montant']) . ' FCFA enregistré.');
    }
}
```

---

## Phase 4 — Vues Blade

### 4a. `resources/views/dashboard/credits/index.blade.php`

```
┌──────────────────────────────────────────────────────────────┐
│ Crédits clients                               [+ Nouveau]   │
│                                                              │
│ ┌──────┬──────┬──────┬──────┐                                │
│ │ 12   │ 8    │ 2 🔴 │ 2 ✅ │                                │
│ │Total │En crs│Retard│Soldés│                                │
│ └──────┴──────┴──────┴──────┘                                │
│                                                              │
│ [Tous] [En cours] [En retard] [Soldés]    🔍 ______________ │
│                                                              │
│ Client    │ Article   │ Total  │ Payé  │ Reste  │ Statut    │
│ Jean K.   │ PC HP    │ 500k   │ 200k  │ 300k  │ 🟢 En crs │
│ Marie T.  │ Tél.     │ 300k   │ 300k  │ 0     │ ✅ Soldé   │
│ Paul A.   │ Moto     │ 800k   │ 100k  │ 700k  │ 🔴 Retard │
│                                                              │
│ ← 1 2 3 ... →                                               │
└──────────────────────────────────────────────────────────────┘
```

### 4b. `resources/views/dashboard/credits/show.blade.php`

Détail d'un crédit avec :
- Infos client + vente
- Barre de progression (payé / total)
- Tableau des échéances avec boutons « Encaisser »
- Historique des paiements

---

## Phase 5 — Détection retards & Relances

### 5a. Commande Artisan `credits:detecter-retards`

```php
// app/Console/Commands/DetecterCreditsEnRetard.php
class DetecterCreditsEnRetard extends Command
{
    protected $signature = 'credits:detecter-retards';
    protected $description = 'Détecte les échéances en retard et met à jour les statuts';

    public function handle()
    {
        $echeances = Echeance::where('statut', 'en_attente')
            ->where('date_prevue', '<', now()->toDateString())
            ->get();

        foreach ($echeances as $e) {
            DB::transaction(function () use ($e) {
                $e->statut = 'retard';
                $e->save();
                $e->credit->statut = 'retard';
                $e->credit->save();
                $e->credit->vente->credit_statut = 'retard';
                $e->credit->vente->save();
            });
        }

        $this->info("{$echeances->count()} échéance(s) marquée(s) en retard.");
    }
}
```

Planifier dans `routes/console.php` :

```php
Schedule::command('credits:detecter-retards')->dailyAt('08:00');
```

### 5b. Relances (phase ultérieure)

- Template de message WhatsApp/SMS
- Bouton « Envoyer rappel » depuis la fiche crédit
- Lien vers la vitrine ou un numéro de contact

---

## 📊 Suivi d'implémentation

| Tâche | Fichier(s) | Statut | Date |
|-------|-----------|--------|------|
| Migration `mode_paiement` | Migration | ⬜ | — |
| Migration `credits` | Migration | ⬜ | — |
| Migration `echeances` | Migration | ⬜ | — |
| Migration `paiements_credit` | Migration | ⬜ | — |
| Migration `ventes` (colonnes) | Migration | ⬜ | — |
| Modèle `Credit` | `app/Models/Credit.php` | ⬜ | — |
| Modèle `Echeance` | `app/Models/Echeance.php` | ⬜ | — |
| Modèle `PaiementCredit` | `app/Models/PaiementCredit.php` | ⬜ | — |
| MAJ Modèle `Vente` | `app/Models/Vente.php` | ⬜ | — |
| MAJ Modèle `Client` | `app/Models/Client.php` | ⬜ | — |
| Bouton Crédit caisse | `caisse.blade.php` | ⬜ | — |
| Panneau Crédit caisse | `caisse.blade.php` | ⬜ | — |
| Logique Alpine crédit | `caisse.js` | ⬜ | — |
| `validerVenteCredit()` | `Caisse.php` | ⬜ | — |
| Routes crédits | `web.php` | ⬜ | — |
| `CreditController` | Contrôleur | ⬜ | — |
| Vue index crédits | `credits/index.blade.php` | ⬜ | — |
| Vue détail crédit | `credits/show.blade.php` | ⬜ | — |
| Détection retards | `Console/Commands/` | ⬜ | — |
| Planification retards | `console.php` | ⬜ | — |

---

*Document généré le 26 juin 2026 — sera mis à jour au fil de l'implémentation*
