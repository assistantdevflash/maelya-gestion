<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

class Institut extends Model
{
    use HasUuids;

    protected $table = 'instituts';

    protected $fillable = [
        'nom', 'slug', 'email', 'telephone', 'ville', 'type', 'logo', 'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->nom) . '-' . Str::random(5);
            }
        });
    }

    public function users()
    {
        return $this->hasMany(User::class, 'institut_id');
    }

    public function proprietaire()
    {
        return $this->belongsTo(User::class, 'proprietaire_id');
    }

    /**
     * L'abonnement actif, récupéré via le propriétaire (proprietaire_id) de l'institut.
     * Fonctionne pour les instituts principaux ET secondaires.
     */
    public function getAbonnementActifAttribute()
    {
        return $this->proprietaire?->abonnementActif;
    }

    public function clients()
    {
        return $this->hasMany(Client::class, 'institut_id');
    }

    public function ventes()
    {
        return $this->hasMany(Vente::class, 'institut_id');
    }

    public function produits()
    {
        return $this->hasMany(Produit::class, 'institut_id');
    }

    public function prestations()
    {
        return $this->hasMany(Prestation::class, 'institut_id');
    }

    public function getLogoUrlAttribute(): string
    {
        return $this->logo ? asset('storage/' . $this->logo) : asset('images/logo-placeholder.png');
    }
}
