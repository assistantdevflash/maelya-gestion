<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Notifications\ResetPasswordMaelya;
use App\Notifications\VerifyEmailMaelya;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuids;

    protected $fillable = [
        'name', 'prenom', 'nom_famille', 'email', 'password',
        'telephone', 'role', 'avatar', 'actif', 'institut_id',
        'code_parrainage', 'parraine_par',
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

    public function isCommercial(): bool
    {
        return $this->role === 'commercial';
    }

    public function commercialProfile()
    {
        return $this->hasOne(CommercialProfile::class);
    }

    /**
     * Indique si l'utilisateur a un plan Entreprise actif.
     */
    public function aPlanEntreprise(): bool
    {
        return $this->aFonctionnalite('multi_instituts');
    }

    /**
     * Slug du plan actif, ou du dernier plan expiré (mode lecture seule).
     * Pour les employés, retourne le slug du plan du propriétaire de l'institut.
     */
    public function planActuelSlug(): ?string
    {
        if ($this->isEmploye()) {
            $owner = $this->institut?->proprietaire_id
                ? static::find($this->institut->proprietaire_id)
                : null;
            return $owner?->planActuelSlug();
        }

        // Plan actif
        if ($slug = $this->abonnementActif?->plan?->slug) {
            return $slug;
        }

        // Aucun plan actif → utiliser le dernier plan expiré (pour affichage en lecture seule)
        return $this->abonnements()
            ->where('statut', 'actif')
            ->latest('expire_le')
            ->first()?->plan?->slug;
    }

    /**
     * Vérifie si le plan actuel donne accès à une fonctionnalité.
     * Source de vérité : config/plans-features.php
     */
    public function aFonctionnalite(string $feature): bool
    {
        // Super admin et commercial ont accès à tout
        if ($this->isSuperAdmin() || $this->isCommercial()) {
            return true;
        }

        $slug = $this->planActuelSlug();
        if (!$slug) {
            return false;
        }

        $features = config("plans-features.plans.$slug", []);
        return in_array('*', $features, true) || in_array($feature, $features, true);
    }

    /**
     * Retourne l'ID de l'institut courant (session ou fallback).
     */
    public function currentInstitutId(): string
    {
        return session('current_institut_id', $this->institut_id) ?? $this->institut_id;
    }

    // ── Parrainage ────────────────────────────────────────────────────────────

    /**
     * Le code parrainage est actif si l'utilisateur a un abonnement valide
     * (actif ou en période de sursis de 2 jours).
     * Au-delà, le code est suspendu pour éviter les gains sans abonnement actif.
     */
    public function isParrainageActif(): bool
    {
        if ($this->abonnementActif) {
            return true;
        }
        return $this->abonnementEnSursis() !== null;
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->code_parrainage)) {
                do {
                    $code = strtoupper(Str::random(8));
                } while (static::where('code_parrainage', $code)->exists());
                $user->code_parrainage = $code;
            }
        });
    }

    public function parrain()
    {
        return $this->belongsTo(User::class, 'parraine_par');
    }

    public function filleuls()
    {
        return $this->hasMany(User::class, 'parraine_par');
    }

    public function parrainagesEffectues()
    {
        return $this->hasMany(Parrainage::class, 'parrain_id');
    }

    public function parrainageRecu()
    {
        return $this->hasOne(Parrainage::class, 'filleul_id');
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailMaelya);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordMaelya($token));
    }
}

