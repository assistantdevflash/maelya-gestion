<?php

namespace App\Models;

use App\Traits\BelongsToInstitut;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasUuids, SoftDeletes, BelongsToInstitut;

    protected $fillable = [
        'institut_id', 'prenom', 'nom', 'telephone', 'email',
        'date_naissance', 'notes', 'points_fidelite', 'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'date_naissance' => 'date',
        'points_fidelite' => 'integer',
    ];

    public function ventes()
    {
        return $this->hasMany(Vente::class, 'client_id');
    }

    public function historiquePoints()
    {
        return $this->hasMany(HistoriquePoints::class, 'client_id');
    }

    public function getNomCompletAttribute(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }

    public function getTotalDepenseAttribute(): float
    {
        return $this->ventes()->where('statut', 'validee')->sum('total');
    }

    public function getNombreVisitesAttribute(): int
    {
        return $this->ventes()->where('statut', 'validee')->count();
    }

    public function getDerniereVisiteAttribute()
    {
        return $this->ventes()->where('statut', 'validee')->latest()->value('created_at');
    }

    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    public function codesReductionFidelite()
    {
        return $this->hasMany(CodeReduction::class, 'client_id')
            ->where('code', 'like', 'FID-%');
    }
}
