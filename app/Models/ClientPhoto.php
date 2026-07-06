<?php

namespace App\Models;

use App\Traits\BelongsToInstitut;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ClientPhoto extends Model
{
    use HasUuids, BelongsToInstitut;

    protected $fillable = [
        'institut_id', 'client_id', 'user_id', 'type', 'path', 'mime_type', 'extension', 'legende', 'date_prise',
    ];

    protected $casts = [
        'date_prise' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf' || $this->extension === 'pdf';
    }

    public function isImage(): bool
    {
        return !$this->isPdf();
    }

    protected static function booted()
    {
        static::deleting(function ($photo) {
            if ($photo->path && Storage::disk('public')->exists($photo->path)) {
                Storage::disk('public')->delete($photo->path);
            }
        });
    }
}
