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
        'nom', 'slug', 'email', 'telephone', 'ville', 'adresse', 'type', 'logo', 'actif', 'en_sommeil', 'sommeil_motif',
        'derniere_activite_le', 'derniere_activite_type', 'derniere_activite_user_id',
        // Colonne ajoutée après coup : sans elle, un create() la perdait
        // silencieusement. Toujours renseignée côté serveur.
        'proprietaire_id',
        'vitrine_active', 'reservation_en_ligne',
        'boutique_active', 'boutique_frais_livraison', 'boutique_zones_livraison', 'boutique_delai_livraison', 'boutique_conditions',
        'boutique_option_active', 'boutique_option_expire_le', 'boutique_option_prix',
        'facebook_pixel_id', 'facebook_access_token', 'facebook_test_code', 'facebook_pixel_name', 'facebook_connected_at',
        'couleur_primaire', 'couleur_secondaire', 'couleur_accent',
        // Paramétrage du module crédits
        'credit_plafond_defaut', 'credit_relance_active', 'credit_message_relance',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'en_sommeil' => 'boolean',
        'derniere_activite_le' => 'datetime',
        'vitrine_active' => 'boolean',
        'reservation_en_ligne' => 'boolean',
        'boutique_active' => 'boolean',
        'boutique_option_active' => 'boolean',
        'boutique_option_expire_le' => 'date',
        'boutique_option_prix' => 'integer',
        'boutique_frais_livraison' => 'decimal:2',
        'boutique_zones_livraison' => 'array',
        'facebook_access_token' => 'encrypted',
        'facebook_connected_at' => 'datetime',
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

    /**
     * Établissement mis en sommeil pour dépassement de quota du plan.
     * Distinct de `actif` : réversible automatiquement, aucune donnée perdue.
     */
    public function estEnSommeil(): bool
    {
        return (bool) $this->en_sommeil;
    }

    /** Établissement utilisable : ni désactivé par l'admin, ni en sommeil. */
    public function estUtilisable(): bool
    {
        return (bool) $this->actif && !$this->en_sommeil;
    }

    public function scopeUtilisables($query)
    {
        return $query->where('actif', true)->where('en_sommeil', false);
    }

    /** Auteur de la dernière opération métier enregistrée. */
    public function auteurDerniereActivite()
    {
        return $this->belongsTo(User::class, 'derniere_activite_user_id');
    }

    /** « 26/10/2026 à 14:32 » ou « Jamais ». */
    public function getDerniereActiviteFormateeAttribute(): string
    {
        return $this->derniere_activite_le
            ? $this->derniere_activite_le->format('d/m/Y à H:i')
            : 'Jamais';
    }

    /** « il y a 3 heures », « il y a 12 jours »… */
    public function getDerniereActiviteHumaineAttribute(): string
    {
        return $this->derniere_activite_le
            ? $this->derniere_activite_le->diffForHumans()
            : 'Jamais';
    }

    public function proprietaire()
    {
        return $this->belongsTo(User::class, 'proprietaire_id');
    }

    /**
     * Propriétaire effectif de l'établissement, avec repli pour les données
     * anciennes où `proprietaire_id` n'a pas été renseigné.
     *
     * Sans ce repli, `$institut->proprietaire` renvoyait null et la boutique
     * publique renvoyait un 404 alors que l'établissement était bien payé.
     */
    public function proprietaireEffectif(): ?User
    {
        if ($this->relationLoaded('proprietaire') && $this->proprietaire) {
            return $this->proprietaire;
        }

        if ($this->proprietaire_id) {
            $owner = User::find($this->proprietaire_id);
            if ($owner) {
                $this->setRelation('proprietaire', $owner);
                return $owner;
            }
        }

        // Repli legacy : plus ancien utilisateur gestionnaire rattaché à l'établissement
        $owner = User::where('institut_id', $this->id)
            ->whereIn('role', ['admin', 'gerant', 'proprietaire'])
            ->orderByRaw("CASE role WHEN 'proprietaire' THEN 0 WHEN 'admin' THEN 1 ELSE 2 END")
            ->orderBy('created_at')
            ->first();

        if ($owner) {
            $this->setRelation('proprietaire', $owner);
        }

        return $owner;
    }

    /**
     * L'abonnement actif, récupéré via le propriétaire (proprietaire_id) de l'institut.
     * Fonctionne pour les instituts principaux ET secondaires.
     */
    public function getAbonnementActifAttribute()
    {
        return $this->proprietaire?->abonnementActif;
    }

    // ── Option Boutique en ligne (facturation PAR établissement) ─────────────

    /**
     * L'établissement a-t-il payé l'option boutique en ligne ?
     * Vérifie l'activation ET la date d'expiration (alignée sur l'abonnement).
     */
    public function hasBoutiqueOption(): bool
    {
        if (!$this->boutique_option_active) {
            return false;
        }
        // Pas de date d'expiration → considéré actif (rétrocompatibilité)
        if (!$this->boutique_option_expire_le) {
            return true;
        }
        return $this->boutique_option_expire_le->isFuture() || $this->boutique_option_expire_le->isToday();
    }

    /**
     * Activer / désactiver l'option boutique de CET établissement.
     */
    public function setBoutiqueOption(bool $active, ?string $expireLe = null, int $prix = 3900): void
    {
        $this->boutique_option_active = $active;
        $this->boutique_option_expire_le = $expireLe ? \Illuminate\Support\Carbon::parse($expireLe)->toDateString() : null;
        $this->boutique_option_prix = $prix;
    }

    /**
     * Prix mensuel de l'option boutique pour cet établissement.
     */
    public function getBoutiqueOptionPrixMensuel(): int
    {
        return $this->boutique_option_prix ?: 3900;
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

    public function commandes()
    {
        return $this->hasMany(Commande::class, 'institut_id');
    }

    public function factures()
    {
        return $this->hasMany(Facture::class, 'institut_id');
    }

    public function devis()
    {
        return $this->hasMany(\App\Models\Devis::class, 'institut_id');
    }

    public function rendezVous()
    {
        return $this->hasMany(\App\Models\RendezVous::class, 'institut_id');
    }

    public function credits()
    {
        return $this->hasMany(\App\Models\Credit::class, 'institut_id');
    }

    public function getLogoUrlAttribute(): string
    {
        return $this->logo ? asset('storage/' . $this->logo) : asset('images/logo-placeholder.png');
    }
}
