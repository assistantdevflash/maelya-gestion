<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CommercialParrainage extends Model
{
    use HasUuids;

    protected $table = 'commercial_parrainages';

    protected $fillable = ['commercial_id', 'proprietaire_id', 'expire_le'];

    protected $casts = ['expire_le' => 'date'];

    public function commercial()
    {
        return $this->belongsTo(CommercialProfile::class, 'commercial_id');
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
}
