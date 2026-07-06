<?php

namespace App\Models;

use App\Traits\BelongsToInstitut;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Commande extends Model
{
    use HasUuids, BelongsToInstitut;

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
        });
    }

    /**
     * Générer un numéro unique de commande
     */
    public static function genererNumero(string $institutId): string
    {
        $prefix = 'CMD';
        $date = now()->format('Ymd');
        
        // Trouver le dernier numéro du jour
        $lastCommande = self::where('institut_id', $institutId)
            ->where('numero', 'like', "$prefix-$date-%")
            ->orderBy('numero', 'desc')
            ->first();

        if ($lastCommande) {
            $lastNumber = (int) substr($lastCommande->numero, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $date, $newNumber);
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
