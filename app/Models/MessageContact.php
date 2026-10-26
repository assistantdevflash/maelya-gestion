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
        'user_agent', 'est_spam', 'spam_score', 'spam_raison',
    ];

    protected $casts = [
        'lu'        => 'boolean',
        'est_spam'  => 'boolean',
        'spam_score' => 'integer',
    ];

    /** Aperçu d'une ligne, utilisé comme « sujet » dans la liste. */
    public function getApercuAttribute(): string
    {
        return \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', (string) $this->message), 90);
    }

    /** Initiales pour l'avatar de la liste. */
    public function getInitialesAttribute(): string
    {
        $mots = preg_split('/\s+/', trim((string) $this->nom));
        $a = mb_substr($mots[0] ?? '?', 0, 1);
        $b = mb_substr($mots[1] ?? '', 0, 1);

        return mb_strtoupper($a . $b) ?: '?';
    }

    public function scopeNonLus($query)
    {
        return $query->where('lu', false)->where('est_spam', false);
    }

    public function scopeSpam($query)
    {
        return $query->where('est_spam', true);
    }
}
