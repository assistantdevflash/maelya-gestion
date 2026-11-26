<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CommercialCommission extends Model
{
    use HasUuids;

    protected $table = 'commercial_commissions';

    protected $fillable = [
        'commercial_id', 'parrainage_id', 'abonnement_id',
        'montant_base', 'taux', 'montant',
        'statut', 'payee_le', 'paye_par', 'notes_paiement',
    ];

    protected $casts = ['payee_le' => 'datetime', 'montant' => 'integer', 'montant_base' => 'integer'];

    public function commercial()
    {
        return $this->belongsTo(CommercialProfile::class, 'commercial_id');
    }

    /** Super admin qui a marqué la commission payée. */
    public function payePar()
    {
        return $this->belongsTo(User::class, 'paye_par');
    }

    public function parrainage()
    {
        return $this->belongsTo(CommercialParrainage::class, 'parrainage_id');
    }

    public function abonnement()
    {
        return $this->belongsTo(Abonnement::class, 'abonnement_id');
    }

    public function getMontantFormatteAttribute(): string
    {
        return number_format($this->montant, 0, ',', ' ') . ' FCFA';
    }

    public function estPayee(): bool
    {
        return $this->statut === 'payee';
    }
}
