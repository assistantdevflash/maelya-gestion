<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToInstitut;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AvisClient extends Model
{
    use HasUuids, BelongsToInstitut, Auditable;

    protected $table = 'avis_clients';

    protected $fillable = [
        'institut_id', 'client_id', 'rdv_id', 'vente_id',
        'token', 'note', 'commentaire', 'statut',
        'client_nom_snap', 'repondu_le',
    ];

    protected $casts = [
        'note'       => 'integer',
        'repondu_le' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $a) {
            if (empty($a->token)) {
                $a->token = Str::random(48);
            }
        });
    }

    public function institut(): BelongsTo
    {
        return $this->belongsTo(Institut::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function rdv(): BelongsTo
    {
        return $this->belongsTo(RendezVous::class, 'rdv_id');
    }

    public function vente(): BelongsTo
    {
        return $this->belongsTo(Vente::class);
    }

    public function auditLabel(): string
    {
        return 'Avis #' . substr($this->id, 0, 8);
    }
}
