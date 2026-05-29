<?php

namespace App\Models;

use App\Traits\BelongsToInstitut;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasUuids, BelongsToInstitut;

    protected $fillable = [
        'institut_id', 'user_id', 'action', 'subject_type', 'subject_id',
        'label', 'changes', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->morphTo();
    }

    /** Logge une action arbitraire */
    public static function record(string $action, ?Model $subject = null, ?string $label = null, array $changes = []): self
    {
        $institutId = session('current_institut_id');
        if (!$institutId && auth()->check()) {
            $institutId = auth()->user()->institut_id;
        }

        return static::create([
            'institut_id'  => $institutId,
            'user_id'      => auth()->id(),
            'action'       => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id'   => $subject?->getKey(),
            'label'        => $label ?? ($subject ? class_basename($subject) . ' ' . $subject->getKey() : null),
            'changes'      => $changes ?: null,
            'ip_address'   => request()?->ip(),
            'user_agent'   => substr((string) request()?->userAgent(), 0, 255),
        ]);
    }
}
