<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CommercialParrainage extends Model
{
    use HasUuids;

    protected $table = 'commercial_parrainages';

    protected $fillable = [
        'commercial_id', 'proprietaire_id', 'institut_id',
        'expire_le', 'source', 'attribue_par',
    ];

    protected $casts = ['expire_le' => 'date'];

    public function commercial()
    {
        return $this->belongsTo(CommercialProfile::class, 'commercial_id');
    }

    /** Établissement ayant servi de point d'entrée (rattachement manuel). */
    public function institut()
    {
        return $this->belongsTo(Institut::class, 'institut_id');
    }

    /** Super admin qui a rattaché le compte à la main. */
    public function attribuePar()
    {
        return $this->belongsTo(User::class, 'attribue_par');
    }

    public function proprietaire()
    {
        return $this->belongsTo(User::class, 'proprietaire_id');
    }

    public function commissions()
    {
        return $this->hasMany(CommercialCommission::class, 'parrainage_id');
    }

    public function isActif(): bool
    {
        return $this->expire_le?->isFuture() ?? false;
    }

    /** Parrainages encore dans leur période de commission. */
    public function scopeActifs($query)
    {
        return $query->whereDate('expire_le', '>=', now()->toDateString());
    }

    /** Vrai si le rattachement a été fait par un super admin (et non par code). */
    public function estManuel(): bool
    {
        return $this->source === 'manuel';
    }
}
