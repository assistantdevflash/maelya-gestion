<?php

namespace App\Models;

use App\Traits\BelongsToInstitut;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Commande extends Model
{
    use HasUuids, BelongsToInstitut, \App\Traits\EnregistreActivite;

    protected $fillable = [
        'institut_id',
        'client_id',
        'vente_id',
        'numero',
        'client_prenom',
        'client_nom',
        'client_telephone',
        'client_email',
        'client_adresse',
        'sous_total',
        'frais_livraison',
        'total',
        'statut',
        'token',
        'date_livraison_prevue',
        'creneau_livraison',
        'stock_decremente_at',
        'relance_envoyee_at',
        'annulee_par',
        'motif_annulation',
        'acceptee_at',
        'en_preparation_at',
        'en_livraison_at',
        'livree_at',
        'annulee_at',
        'payee',
        'payee_at',
        'mode_paiement',
        'notes_client',
        'notes_admin',
    ];

    protected $casts = [
        'sous_total' => 'decimal:2',
        'frais_livraison' => 'decimal:2',
        'total' => 'decimal:2',
        'payee' => 'boolean',
        'acceptee_at' => 'datetime',
        'en_preparation_at' => 'datetime',
        'en_livraison_at' => 'datetime',
        'livree_at' => 'datetime',
        'annulee_at' => 'datetime',
        'payee_at' => 'datetime',
        'stock_decremente_at' => 'datetime',
        'relance_envoyee_at' => 'datetime',
        'date_livraison_prevue' => 'date',
    ];

    /**
     * Boot du modèle
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($commande) {
            if (empty($commande->numero)) {
                $commande->numero = self::genererNumero($commande->institut_id);
            }
            if (empty($commande->token)) {
                $commande->token = \Illuminate\Support\Str::random(48);
            }
        });
    }

    /**
     * Générer un numéro unique de commande
     * IMPORTANT: la contrainte unique est globale, donc on cherche le max global du jour
     */
    public static function genererNumero(string $institutId): string
    {
        return DB::transaction(function () {
            $date   = now()->format('Ymd');
            $prefix = "CMD-{$date}-";

            // La contrainte d'unicité du numéro est globale : la séquence est
            // partagée par tous les établissements, on ignore donc le scope.
            // Le suffixe est complété à 4 chiffres, l'ordre alphabétique suit
            // donc l'ordre numérique (aucune fonction SQL propre à MySQL).
            $lastCommande = self::withoutGlobalScopes()
                ->where('numero', 'like', $prefix . '%')
                ->orderByDesc('numero')
                ->lockForUpdate()
                ->first();

            $lastNumber = $lastCommande
                ? ((int) substr($lastCommande->numero, strrpos($lastCommande->numero, '-') + 1)) + 1
                : 1;

            return sprintf('CMD-%s-%04d', $date, $lastNumber);
        });
    }

    /**
     * Relations
     */
    public function institut(): BelongsTo
    {
        return $this->belongsTo(Institut::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function vente(): BelongsTo
    {
        return $this->belongsTo(Vente::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CommandeItem::class);
    }

    /**
     * Scopes
     */
    public function scopeNouvelles($query)
    {
        return $query->where('statut', 'nouvelle');
    }

    public function scopeEnCours($query)
    {
        return $query->whereIn('statut', ['acceptee', 'en_preparation', 'en_livraison']);
    }

    public function scopeLivrees($query)
    {
        return $query->where('statut', 'livree');
    }

    public function scopePayees($query)
    {
        return $query->where('payee', true);
    }

    public function scopeNonPayees($query)
    {
        return $query->where('payee', false);
    }

    /**
     * Méthodes métier
     */
    public function peutEtreAnnulee(): bool
    {
        return in_array($this->statut, ['nouvelle', 'acceptee', 'en_preparation']);
    }

    public function peutEtreMarqueePayee(): bool
    {
        return $this->statut === 'livree' && !$this->payee;
    }

    /** Le stock de cette commande est-il déjà sorti du magasin ? */
    public function stockReserve(): bool
    {
        return $this->stock_decremente_at !== null;
    }

    /** Le client peut-il encore annuler sa propre commande ? */
    public function peutEtreAnnuleeParClient(): bool
    {
        return ! $this->payee && in_array($this->statut, ['nouvelle', 'acceptee'], true);
    }

    /** Prochain statut logique (pour l'action contextuelle de la fiche). */
    public function prochainStatut(): ?string
    {
        return match ($this->statut) {
            'nouvelle'       => 'acceptee',
            'acceptee'       => 'en_preparation',
            'en_preparation' => 'en_livraison',
            'en_livraison'   => 'livree',
            default          => null,
        };
    }

    public function libelleProchainStatut(): ?string
    {
        return match ($this->prochainStatut()) {
            'acceptee'       => 'Accepter la commande',
            'en_preparation' => 'Passer en préparation',
            'en_livraison'   => 'Marquer expédiée',
            'livree'         => 'Marquer livrée',
            default          => null,
        };
    }

    /** Jours restants avant la livraison promise (négatif si dépassée). */
    public function getJoursLivraisonAttribute(): ?int
    {
        if (! $this->date_livraison_prevue) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->date_livraison_prevue->startOfDay(), false);
    }

    public function getLivraisonEnRetardAttribute(): bool
    {
        $jours = $this->jours_livraison;

        return $jours !== null && $jours < 0 && ! in_array($this->statut, ['livree', 'annulee', 'refusee'], true);
    }

    public function changerStatut(string $nouveauStatut): void
    {
        $this->statut = $nouveauStatut;

        // Mettre à jour les timestamps
        $field = $nouveauStatut . '_at';
        if (in_array($field, ['acceptee_at', 'en_preparation_at', 'en_livraison_at', 'livree_at', 'annulee_at'])) {
            $this->$field = now();
        }

        $this->save();
    }
}
