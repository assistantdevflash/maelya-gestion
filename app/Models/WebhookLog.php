<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    protected $fillable = [
        'provider', 'event', 'webhook_id', 'transaction_id',
        'payload', 'headers', 'signature', 'signature_valid',
        'status', 'processing_error', 'processed_at',
    ];

    protected $casts = [
        'signature_valid' => 'boolean',
        'processed_at'    => 'datetime',
    ];

    public function transaction()
    {
        return $this->belongsTo(PaymentTransaction::class, 'transaction_id');
    }
}
