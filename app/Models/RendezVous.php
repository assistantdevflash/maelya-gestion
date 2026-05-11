<?php

namespace App\Models;

use App\Traits\BelongsToInstitut;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RendezVous extends Model
{
    use HasUuids, BelongsToInstitut;

    protected $table = 'rendez_vous';

    protected $fillable = [
        'institut_id',
        'client_id',
        'client_nom',
        'client_telephone',
        'client_email',
        'employe_id',
        'debut_le',
        'duree_minutes',
        'statut',
        'notes',
        'prestation_libre',
        'rappel_envoye',
    ];

    protected $casts = [
        'debut_le'       => 'datetime',
        'duree_minutes'  => 'integer',
        'rappel_envoye'  => 'boolean',
    ];

    // ── Relations ──────────────────────────────────────────────────────────

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function employe()
    {
        return $this->belongsTo(User::class, 'employe_id');
    }

    public function prestations()
    {
        return $this->belongsToMany(Prestation::class, 'rendez_vous_prestations', 'rendez_vous_id', 'prestation_id');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeAVenir($query)
    {
        return $query->where('debut_le', '>=', now())
                     ->whereIn('statut', ['en_attente', 'confirme']);
    }

    public function scopeAujourdhui($query)
    {
        return $query->whereDate('debut_le', today());
    }

    public function scopeNonAnnule($query)
    {
        return $query->where('statut', '!=', 'annule');
    }

    // ── Accesseurs ─────────────────────────────────────────────────────────

    public function getFinLeAttribute()
    {
        return $this->debut_le?->copy()->addMinutes($this->duree_minutes);
    }

    public function getLabelPrestationsAttribute(): string
    {
        if ($this->relationLoaded('prestations') && $this->prestations->isNotEmpty()) {
            return $this->prestations->pluck('nom')->implode(', ');
        }
        return $this->prestation_libre ?? '—';
    }

    public function getStatutBadgeAttribute(): array
    {
        return match ($this->statut) {
            'en_attente' => ['label' => 'En attente', 'color' => 'amber'],
            'confirme'   => ['label' => 'Confirmé',   'color' => 'blue'],
            'termine'    => ['label' => 'Terminé',    'color' => 'emerald'],
            'annule'     => ['label' => 'Annulé',     'color' => 'red'],
            default      => ['label' => $this->statut, 'color' => 'gray'],
        };
    }
}
