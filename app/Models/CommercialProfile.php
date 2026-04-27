<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CommercialProfile extends Model
{
    use HasUuids;

    protected $table = 'commercial_profiles';

    protected $fillable = ['user_id', 'code', 'telephone', 'notes'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parrainages()
    {
        return $this->hasMany(CommercialParrainage::class, 'commercial_id');
    }

    public function commissions()
    {
        return $this->hasMany(CommercialCommission::class, 'commercial_id');
    }

    public function totalGagne(): int
    {
        return (int) $this->commissions()->where('statut', 'payee')->sum('montant');
    }

    public function totalEnAttente(): int
    {
        return (int) $this->commissions()->where('statut', 'en_attente')->sum('montant');
    }
}
