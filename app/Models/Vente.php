<?php

namespace App\Models;

use App\Traits\BelongsToInstitut;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Vente extends Model
{
    use HasUuids, BelongsToInstitut;

    protected $fillable = [
        'institut_id', 'client_id', 'user_id', 'numero', 'total',
        'remise', 'code_reduction_id',
        'mode_paiement', 'reference_paiement', 'montant_cash', 'montant_mobile', 'montant_carte',
        'statut', 'notes', 'ip_address',
    ];

    protected $casts = [
        'total'          => 'integer',
        'remise'         => 'integer',
        'montant_cash'   => 'integer',
        'montant_mobile' => 'integer',
        'montant_carte'  => 'integer',
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

    public function scopeValidee($query)
    {
        return $query->where('statut', 'validee');
    }

    public function getTotalFormatteAttribute(): string
    {
        return number_format($this->total, 0, ',', ' ') . ' FCFA';
    }
}
