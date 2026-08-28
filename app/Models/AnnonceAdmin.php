<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AnnonceAdmin extends Model
{
    protected $table = 'annonces_admin';

    protected $fillable = [
        'expediteur_id',
        'titre',
        'message',
        'type',
        'cible',
        'instituts_ids',
        'actif',
        'expire_le',
    ];

    protected $casts = [
        'instituts_ids' => 'array',
        'actif' => 'boolean',
        'expire_le' => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function expediteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'expediteur_id');
    }

    public function lecteurs(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'annonce_lectures', 'annonce_id', 'user_id')
            ->withPivot('lu_le');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActives($query)
    {
        return $query->where('actif', true)
            ->where(function ($q) {
                $q->whereNull('expire_le')
                  ->orWhere('expire_le', '>', now());
            });
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Vérifie si un utilisateur a lu cette annonce
     */
    public function estLuePar(User $user): bool
    {
        return $this->lecteurs()->where('user_id', $user->id)->exists();
    }

    /**
     * Marque l'annonce comme lue par un utilisateur
     */
    public function marquerCommeLue(User $user): void
    {
        if (!$this->estLuePar($user)) {
            $this->lecteurs()->attach($user->id, ['lu_le' => now()]);
        }
    }

    /**
     * Vérifie si l'annonce cible un utilisateur donné
     */
    public function cibleUtilisateur(User $user): bool
    {
        // Tous les établissements
        if ($this->cible === 'tous') {
            return true;
        }

        // Sélection ou un seul
        if (in_array($this->cible, ['selection', 'un'])) {
            $institutIds = $this->instituts_ids ?? [];
            return in_array($user->institut_id, $institutIds);
        }

        return false;
    }

    /**
     * Classes CSS pour le style de la bannière
     */
    public function getStyleClasses(): string
    {
        return match($this->type) {
            'success' => 'bg-green-50 dark:bg-green-500/10 border-green-200 dark:border-green-500/20 text-green-800 dark:text-green-300',
            'warning' => 'bg-amber-50 dark:bg-amber-500/10 border-amber-200 dark:border-amber-500/20 text-amber-800 dark:text-amber-300',
            'danger' => 'bg-red-50 dark:bg-red-500/10 border-red-200 dark:border-red-500/20 text-red-800 dark:text-red-300',
            default => 'bg-blue-50 dark:bg-blue-500/10 border-blue-200 dark:border-blue-500/20 text-blue-800 dark:text-blue-300',
        };
    }

    /**
     * Icône SVG selon le type
     */
    public function getIconSvg(): string
    {
        return match($this->type) {
            'success' => '<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
            'warning' => '<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>',
            'danger' => '<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
            default => '<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
        };
    }
}
