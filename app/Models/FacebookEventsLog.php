<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacebookEventsLog extends Model
{
    protected $fillable = [
        'institut_id', 'event_name', 'source', 'payload', 'success',
    ];

    protected $casts = [
        'payload' => 'array',
        'success' => 'boolean',
    ];

    public function institut()
    {
        return $this->belongsTo(Institut::class);
    }
}
