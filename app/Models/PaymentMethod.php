<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends Model
{
    protected $fillable = [
        'code', 'name', 'type', 'provider', 'logo_url', 'description',
        'is_active', 'boutique_checkout_enabled', 'auto_validate', 'position', 'config',
        'supported_currencies', 'supported_countries',
    ];

    protected $casts = [
        'is_active'            => 'boolean',
        'boutique_checkout_enabled' => 'boolean',
        'auto_validate'        => 'boolean',
        'config'               => 'array',
        'supported_currencies' => 'array',
        'supported_countries'  => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('position');
    }

    /** Moyens de paiement proposés au moment de la commande boutique. */
    public function scopePourCheckoutBoutique($query)
    {
        return $query->where('is_active', true)
            ->where('boutique_checkout_enabled', true)
            ->orderBy('position');
    }

    public function isGateway(): bool
    {
        return $this->type === 'gateway';
    }

    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }
}
