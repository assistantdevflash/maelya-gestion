<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuids;

    protected $fillable = [
        'name', 'prenom', 'nom_famille', 'email', 'password',
        'telephone', 'role', 'avatar', 'actif', 'institut_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'actif' => 'boolean',
        ];
    }

    public function institut()
    {
        return $this->belongsTo(Institut::class, 'institut_id');
    }

    /**
     * Tous les instituts dont cet utilisateur est propriétaire.
     */
    public function mesInstituts()
    {
        return $this->hasMany(Institut::class, 'proprietaire_id');
    }

    /**
     * L'institut actif dans la session (multi-institut).
     */
    public function getInstitutActifAttribute(): ?Institut
    {
        $id = session('current_institut_id', $this->institut_id);
        if ($id && $id !== $this->institut_id) {
            return Institut::find($id) ?? $this->institut;
        }
        return $this->institut;
    }

    public function ventes()
    {
        return $this->hasMany(Vente::class, 'user_id');
    }

    public function abonnements()
    {
        return $this->hasMany(Abonnement::class, 'user_id');
    }

    public function abonnementActif()
    {
        return $this->hasOne(Abonnement::class, 'user_id')
            ->where('statut', 'actif')
            ->where('expire_le', '>=', now()->toDateString())
            ->latest('expire_le');
    }

    /**
     * Retourne l'abonnement en période de sursis (expiré depuis ≤ 2 jours).
     */
    public function abonnementEnSursis(): ?Abonnement
    {
        return $this->abonnements()
            ->where('statut', 'actif')
            ->where('expire_le', '<', now()->toDateString())
            ->where('expire_le', '>=', now()->subDays(2)->toDateString())
            ->latest('expire_le')
            ->first();
    }

    public function getNomCompletAttribute(): string
    {
        return trim($this->prenom . ' ' . $this->nom_famille);
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin']);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isEmploye(): bool
    {
        return $this->role === 'employe';
    }

    /**
     * Indique si l'utilisateur a un plan Entreprise actif.
     */
    public function aPlanEntreprise(): bool
    {
        $abo = $this->abonnementActif;
        return $abo && $abo->plan?->slug === 'entreprise';
    }

    /**
     * Retourne l'ID de l'institut courant (session ou fallback).
     */
    public function currentInstitutId(): string
    {
        return session('current_institut_id', $this->institut_id) ?? $this->institut_id;
    }
}

