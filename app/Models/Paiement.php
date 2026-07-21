<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Paiement extends Model
{
    use HasUuids;
    protected $table = 'paiements';
    protected $fillable = ['facture_id','user_id','montant','mode_paiement','reference','date_paiement','notes'];
    protected $casts = ['montant'=>'integer','date_paiement'=>'date'];
    protected $appends = ['mode_paiement_label'];

    public function getModePaiementLabelAttribute(): string
    {
        return [
            'especes' => 'Espèces', 'mobile_money' => 'Mobile Money',
            'virement' => 'Virement', 'cheque' => 'Chèque', 'carte' => 'Carte',
        ][$this->mode_paiement] ?? $this->mode_paiement;
    }
    public function facture(): BelongsTo { return $this->belongsTo(Facture::class); }
    public function encaisseur(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
}
