<?php

namespace App\Models;

use App\Traits\BelongsToInstitut;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Vente extends Model
{
    use HasUuids, BelongsToInstitut, \App\Traits\Auditable, \App\Traits\EnregistreActivite;

    /**
     * Libellés des modes de paiement — source unique.
     *
     * Le tableau de bord affichait « Cash » pour tout ce qui n'était pas du
     * mobile money : une vente à crédit ou par carte s'y affichait comme
     * encaissée en espèces.
     */
    public const MODES_PAIEMENT = [
        'cash'         => 'Espèces',
        'mobile_money' => 'Mobile money',
        'carte'        => 'Carte bancaire',
        'mixte'        => 'Paiement mixte',
        'credit'       => 'Crédit',
    ];

    public static function libelleModePaiement(?string $mode): string
    {
        return self::MODES_PAIEMENT[$mode] ?? ucfirst((string) $mode);
    }

    public function getLibelleModePaiementAttribute(): string
    {
        return self::libelleModePaiement($this->mode_paiement);
    }

    public function auditLabel(): string
    {
        return 'Vente #' . ($this->numero ?? $this->id);
    }

    protected $fillable = [
        'institut_id', 'client_id', 'user_id', 'numero', 'numero_facture', 'total',
        'montant_paye', 'remise', 'pourboire', 'code_reduction_id',
        'mode_paiement', 'reference_paiement', 'montant_cash', 'montant_mobile', 'montant_carte',
        'statut', 'credit_statut', 'motif_annulation', 'annulee_le', 'annulee_par', 'notes', 'ip_address',
    ];

    protected $casts = [
        'total'          => 'integer',
        'montant_paye'   => 'integer',
        'remise'         => 'integer',
        'pourboire'      => 'integer',
        'montant_cash'   => 'integer',
        'montant_mobile' => 'integer',
        'montant_carte'  => 'integer',
        'annulee_le'     => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->numero)) {
                $model->numero = 'V-' . strtoupper(Str::random(8));
            }
        });
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items()
    {
        return $this->hasMany(VenteItem::class, 'vente_id');
    }

    public function codeReduction()
    {
        return $this->belongsTo(CodeReduction::class, 'code_reduction_id');
    }

    public function avoirs()
    {
        return $this->hasMany(Avoir::class, 'vente_id');
    }

    /** Auteur de l'annulation (colonne annulee_par). */
    public function annuleePar()
    {
        return $this->belongsTo(User::class, 'annulee_par');
    }

    /** Mouvements de stock générés par cette vente (et son annulation). */
    public function mouvementsStock()
    {
        return $this->hasMany(MouvementStock::class, 'vente_id');
    }

    public function credit()
    {
        return $this->hasOne(Credit::class);
    }

    public function scopeValidee($query)
    {
        return $query->where('statut', 'validee');
    }

    public function getTotalFormatteAttribute(): string
    {
        return number_format($this->total, 0, ',', ' ') . ' FCFA';
    }
}
