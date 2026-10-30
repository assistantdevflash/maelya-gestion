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
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuids, HasApiTokens;

    protected $fillable = [
        'name', 'prenom', 'nom_famille', 'email', 'password',
        'telephone', 'role', 'avatar', 'actif', 'institut_id',
        'en_sommeil', 'sommeil_motif',
        'derniere_connexion_le',
        'code_parrainage', 'parraine_par',
        'email_verified_at', 'code_verification_email', 'code_verification_expire_le',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'            => 'datetime',
            'code_verification_expire_le'  => 'datetime',
            'password'                     => 'hashed',
            'actif'                        => 'boolean',
            'en_sommeil'                   => 'boolean',
            'derniere_connexion_le'        => 'datetime',
        ];
    }

    /**
     * Compte mis en sommeil pour dépassement du quota d'employés du plan.
     */
    public function estEnSommeil(): bool
    {
        return (bool) $this->en_sommeil;
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
        // Le sursis de 2 jours s'applique aux plans payants (pas aux essais).
        // On cherche dans les statuts 'actif' (pas encore traité par abonnements:expirer)
        // ET 'expire' (déjà traité par la tâche planifiée mais dans les 2 jours).
        return $this->abonnements()
            ->whereIn('statut', ['actif', 'expire'])
            ->where('expire_le', '<', now()->toDateString())
            ->where('expire_le', '>=', now()->subDays(2)->toDateString())
            ->whereHas('plan', fn ($q) => $q->where('duree_type', '!=', 'essai'))
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

    public function isGerant(): bool
    {
        return $this->role === 'gerant';
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
        // Pour les non-propriétaires (employé, gérant, admin secondaire),
        // utiliser le plan du propriétaire de l'institut.
        if ($this->institut && $this->institut->proprietaire_id !== $this->id) {
            $owner = $this->institut?->proprietaire_id
                ? static::with('abonnementActif.plan')->find($this->institut->proprietaire_id)
                : null;
            return $owner?->planActuelSlug();
        }

        // Plan actif
        if ($slug = $this->abonnementActif?->plan?->slug) {
            return $slug;
        }

        // Aucun plan actif → utiliser le dernier plan expiré (pour affichage en lecture seule)
        return $this->abonnements()
            ->whereIn('statut', ['actif', 'expire'])
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
     * La caisse simplifiée (mode commerce / supermarché) est-elle accessible ?
     *
     * Elle repose entièrement sur le scan de codes-barres, réservé à la formule
     * Premium+. Deux exceptions :
     *  - la période d'essai : toutes les fonctionnalités sont offertes, donc la
     *    caisse simplifiée aussi (c'est justement là qu'on veut la faire essayer) ;
     *  - super admin / commercial.
     */
    public function peutUtiliserCaisseSimplifiee(): bool
    {
        if ($this->isSuperAdmin() || $this->isCommercial()) {
            return true;
        }

        if ($this->aFonctionnalite('scan_code_barre')) {
            return true;
        }

        // Essai gratuit : on se base sur le type de durée du plan, plus fiable
        // que la matrice de features (qui peut restreindre un plan d'essai).
        $abo = $this->abonnementActif ?? $this->abonnementEnSursis();

        if (! $abo) {
            return false;
        }

        return $abo->plan?->duree_type === 'essai'
            || ($abo->plan?->slug ?? $this->planActuelSlug()) === 'essai';
    }

    /**
     * L'utilisateur a-t-il accès au module boutique en ligne pour un établissement ?
     *
     * Depuis le passage à la facturation PAR établissement, l'accès est vérifié
     * pour l'établissement passé en paramètre (ou l'établissement courant si null).
     * L'option boutique (3 900 F/mois) est un add-on payant par établissement.
     * Gratuit pendant l'essai et pour les super admins.
     */
    public function hasBoutiqueAccess(?Institut $institut = null): bool
    {
        // Super admin : toujours
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Déterminer l'établissement concerné (paramètre explicite > établissement courant)
        $institut = $institut ?: $this->institutActif;
        if (!$institut) {
            return false;
        }

        $key = "user_{$this->id}_boutique_access_{$institut->id}";

        // ⚠️ IMPORTANT : on ne mémorise QUE les autorisations (cache positif).
        // Avant, un refus était mis en cache 5 minutes : la moindre condition
        // transitoire (renouvellement, activation de l'option, cache vidé…) figeait
        // un 404 pendant 5 min pour TOUS les visiteurs. C'était la cause principale
        // des erreurs 404 intermittentes de la boutique.
        if (Cache::get($key) === true) {
            return true;
        }

        $autorise = $this->calculerAccesBoutique($institut);

        if ($autorise) {
            Cache::put($key, true, now()->addMinutes(5));
        } else {
            // Un refus n'est jamais conservé : on revérifie à chaque requête.
            Cache::forget($key);
        }

        return $autorise;
    }

    /**
     * Calcul réel (non caché) de l'accès boutique pour un établissement.
     *
     * Règles :
     * - l'abonnement du propriétaire doit être actif (ou en sursis de 2 jours) ;
     * - le plan doit inclure la fonctionnalité « boutique » (essai = tout) ;
     * - en essai : boutique offerte ;
     * - en plan payant : l'option boutique doit être payée POUR CET établissement,
     *   avec une tolérance de 2 jours (sursis) pour ne pas couper brutalement
     *   pendant un renouvellement.
     */
    private function calculerAccesBoutique(Institut $institut): bool
    {
        // Abonnement actif, ou en période de sursis (2 jours)
        $abo = $this->abonnementActif ?? $this->abonnementEnSursis();

        if (!$abo) {
            return false;
        }

        // Vérifier la feature sur le plan réel de l'abonnement (plus fiable que
        // planActuelSlug() qui dépend d'une détection de propriétaire fragile).
        $slug     = $abo->plan?->slug ?? $this->planActuelSlug();
        $estEssai = $abo->plan?->duree_type === 'essai' || $slug === 'essai';

        // Plan essai → boutique offerte (quelle que soit la matrice de features)
        if ($estEssai) {
            return true;
        }

        $features = config("plans-features.plans.$slug", []);
        if (!in_array('*', $features, true) && !in_array('boutique', $features, true)) {
            return false;
        }

        // Plan payant → option payée sur CET établissement
        if ($institut->hasBoutiqueOption()) {
            return true;
        }

        // Tolérance : option encore marquée active mais expirée depuis ≤ 2 jours.
        // Évite une coupure sèche pendant un renouvellement ou un léger décalage
        // de facturation. Une option explicitement désactivée ne bénéficie
        // d'aucune tolérance.
        if ($institut->boutique_option_active
            && $institut->boutique_option_expire_le
            && $institut->boutique_option_expire_le->copy()->addDays(2)->isFuture()) {
            return true;
        }

        return false;
    }

    /**
     * Retourne l'ID de l'institut courant (session ou fallback).
     */
    public function currentInstitutId(): ?string
    {
        return session('current_institut_id', $this->institut_id) ?? $this->institut_id;
    }

    /**
     * Invalide le cache d'accès boutique pour un établissement donné.
     */
    public function forgetBoutiqueAccessCache(?string $institutId = null): void
    {
        $ids = $institutId ? [$institutId] : $this->mesInstituts()->pluck('id')->all();
        foreach ($ids as $id) {
            Cache::forget("user_{$this->id}_boutique_access_{$id}");
        }
    }

    // ── Parrainage ────────────────────────────────────────────────────────────

    /**
     * Le code parrainage est actif si l'utilisateur a un abonnement valide
     * (actif ou en période de sursis de 2 jours).
     * Au-delà, le code est suspendu pour éviter les gains sans abonnement actif.
     */
    public function isParrainageActif(): bool
    {
        return $this->abonnementActif !== null;
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

