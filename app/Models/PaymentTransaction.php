<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    use HasUuids;

    protected $table = 'payment_transactions';

    protected $fillable = [
        'reference', 'user_id', 'institut_id', 'abonnement_id', 'type',
        'amount', 'fees', 'net_amount', 'currency',
        'payment_method_id', 'payment_method_code',
        'gateway_reference', 'gateway_status', 'gateway_response', 'checkout_url',
        'status', 'metadata', 'paid_at', 'expires_at',
    ];

    protected $casts = [
        'amount'     => 'integer',
        'fees'       => 'integer',
        'net_amount' => 'integer',
        'metadata'   => 'array',
        'paid_at'    => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function abonnement()
    {
        return $this->belongsTo(Abonnement::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired'
            || ($this->expires_at && $this->expires_at->isPast() && !$this->isCompleted());
    }

    /** Génère une référence unique lisible */
    public static function generateReference(): string
    {
        return 'MGP-' . strtoupper(substr(uniqid('', true), -8));
    }
}
