<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MessageContact extends Model
{
    use HasUuids;

    protected $table = 'messages_contact';

    protected $fillable = [
        'nom', 'email', 'telephone', 'message', 'lu', 'honeypot', 'ip_address',
    ];

    protected $casts = [
        'lu' => 'boolean',
    ];
}
