<?php

namespace App\Models;

use App\Traits\BelongsToInstitut;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Devis extends Model
{
    use HasUuids, BelongsToInstitut;

    protected $table = 'devis';

    protected $fillable = [
        'institut_id', 'client_id', 'user_id', 'commercial_id',
        'numero', 'statut', 'token',
        'date_creation', 'date_expiration', 'date_acceptation', 'signature_client',
        'client_prenom', 'client_nom', 'client_email', 'client_telephone', 'client_adresse',
        'sous_total', 'remise_globale_type', 'remise_globale_valeur',
        'total_ht', 'tva_applicable', 'tva_taux', 'total_ttc',
        'notes', 'conditions', 'facture_id',
    ];

    protected $casts = [
        'date_creation' => 'date',
        'date_expiration' => 'date',
        'date_acceptation' => 'datetime',
        'sous_total' => 'integer',
        'remise_globale_valeur' => 'integer',
        'total_ht' => 'integer',
        'tva_applicable' => 'boolean',
        'tva_taux' => 'decimal:2',
        'total_ttc' => 'integer',
    ];

    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function createur(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function commercial(): BelongsTo { return $this->belongsTo(User::class, 'commercial_id'); }
    public function items(): HasMany { return $this->hasMany(DevisItem::class)->orderBy('ordre'); }
    public function facture(): BelongsTo { return $this->belongsTo(Facture::class); }

    public function scopeBrouillons($q) { $q->where('statut', 'brouillon'); }
    public function scopeEnvoyes($q) { $q->where('statut', 'envoye'); }
    public function scopeAcceptes($q) { $q->where('statut', 'accepte'); }
    public function scopeEnCours($q) { $q->whereIn('statut', ['brouillon', 'envoye']); }

    public function getEstModifiableAttribute(): bool
    {
        return in_array($this->statut, ['brouillon']);
    }

    public function getClientNomCompletAttribute(): string
    {
        return trim(($this->client_prenom ?? '') . ' ' . ($this->client_nom ?? ''));
    }

    public function getRemiseGlobaleAttribute(): int
    {
        return (int) $this->remise_globale_valeur;
    }

    public function getTotalTvaAttribute(): int
    {
        if (!$this->tva_applicable || $this->tva_taux <= 0) return 0;
        return (int) round($this->total_ht * (float) $this->tva_taux / 100);
    }
}
