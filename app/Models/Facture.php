<?php

namespace App\Models;

use App\Traits\BelongsToInstitut;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Facture extends Model
{
    use HasUuids, BelongsToInstitut;

    protected $table = 'factures';

    protected $fillable = [
        'institut_id', 'client_id', 'devis_id', 'vente_id', 'user_id',
        'numero', 'titre', 'statut', 'token',
        'date_emission', 'date_echeance',
        'client_prenom', 'client_nom', 'client_email', 'client_telephone', 'client_adresse',
        'sous_total', 'remise_globale_type', 'remise_globale_valeur',
        'total_ht', 'tva_applicable', 'tva_taux', 'total_ttc', 'montant_paye',
        'notes', 'conditions',
    ];

    protected $casts = [
        'date_emission' => 'date',
        'date_echeance' => 'date',
        'sous_total' => 'integer',
        'remise_globale_valeur' => 'integer',
        'total_ht' => 'integer',
        'tva_applicable' => 'boolean',
        'tva_taux' => 'decimal:2',
        'total_ttc' => 'integer',
        'montant_paye' => 'integer',
    ];

    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function devis(): BelongsTo { return $this->belongsTo(Devis::class); }
    public function vente(): BelongsTo { return $this->belongsTo(Vente::class); }
    public function createur(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function items(): HasMany { return $this->hasMany(FactureItem::class)->orderBy('ordre'); }
    public function paiements(): HasMany { return $this->hasMany(Paiement::class); }

    public function getResteAPayerAttribute(): int { return max(0, $this->total_ttc - $this->montant_paye); }
    public function getEstPayeeAttribute(): bool { return $this->montant_paye >= $this->total_ttc; }
    public function getClientNomCompletAttribute(): string { return trim(($this->client_prenom ?? '') . ' ' . ($this->client_nom ?? '')); }

    public function getRemiseGlobaleAttribute(): int
    {
        if (!$this->remise_globale_type || !$this->remise_globale_valeur) return 0;
        if ($this->remise_globale_type === 'pourcentage') {
            return (int) round($this->sous_total * (int) $this->remise_globale_valeur / 100);
        }
        return (int) $this->remise_globale_valeur;
    }

    public function getTotalTvaAttribute(): int
    {
        if (!$this->tva_applicable || $this->tva_taux <= 0) return 0;
        return (int) round($this->total_ht * (float) $this->tva_taux / 100);
    }

    public function scopeEnRetard($q) { $q->where('statut', '!=', 'payee')->whereDate('date_echeance', '<', now()); }
}
