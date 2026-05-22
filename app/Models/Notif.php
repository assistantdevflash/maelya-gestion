<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notif extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['user_id', 'type', 'titre', 'corps', 'url', 'lu'];

    protected $casts = ['lu' => 'boolean', 'created_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
