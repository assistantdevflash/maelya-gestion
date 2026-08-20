<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends Model
{
    protected $fillable = [
        'code', 'name', 'type', 'provider', 'logo_url', 'description',
        'is_active', 'auto_validate', 'position', 'config',
        'supported_currencies', 'supported_countries',
    ];

    protected $casts = [
        'is_active'            => 'boolean',
        'auto_validate'        => 'boolean',
        'config'               => 'array',
        'supported_currencies' => 'array',
        'supported_countries'  => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('position');
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
