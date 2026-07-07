<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Abonnement extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'plan_id', 'montant', 'periode',
        'statut', 'debut_le', 'expire_le',
        'reference_transfert', 'preuve_paiement', 'notes_admin', 'valide_par',
        'metadata',
    ];

    protected $casts = [
        'montant' => 'integer',
        'debut_le' => 'date',
        'expire_le' => 'date',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function plan()
    {
        return $this->belongsTo(PlanAbonnement::class, 'plan_id');
    }

    public function validePar()
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    public function isActif(): bool
    {
        return $this->statut === 'actif' && $this->expire_le?->isFuture();
    }

    public function joursRestants(): int
    {
        if (!$this->expire_le || !$this->isActif()) {
            return 0;
        }
        return max(0, now()->diffInDays($this->expire_le, false));
    }

    /**
     * Retourne true si l'abonnement a expiré depuis moins de 2 jours (période de sursis).
     */
    public function enPeriodeSursis(): bool
    {
        if (!$this->expire_le || $this->statut !== 'actif') {
            return false;
        }
        // expire_le est dans le passé ET au plus 2 jours avant aujourd'hui
        $jours = now()->startOfDay()->diffInDays($this->expire_le->copy()->startOfDay(), false);
        return $jours < 0 && $jours >= -2;
    }

    /**
     * Nombre de jours écoulés depuis l'expiration (0 si pas encore expiré).
     */
    public function joursDepuisExpiration(): int
    {
        if (!$this->expire_le) return 0;
        return max(0, (int) abs(now()->startOfDay()->diffInDays($this->expire_le->copy()->startOfDay(), false)));
    }

    // ── Option Boutique en ligne ─────────────────────────────────────────────

    /**
     * L'option boutique en ligne est-elle activée sur cet abonnement ?
     */
    public function hasBoutique(): bool
    {
        return (bool) ($this->metadata['boutique'] ?? false);
    }

    /**
     * Activer ou désactiver l'option boutique.
     */
    public function setBoutique(bool $active, int $prix = 3900): void
    {
        $meta = $this->metadata ?? [];
        $meta['boutique'] = $active;
        $meta['boutique_prix'] = $prix;
        $this->metadata = $meta;
    }

    /**
     * Prix mensuel de l'option boutique.
     */
    public function getBoutiquePrixMensuel(): int
    {
        return $this->metadata['boutique_prix'] ?? 3900;
    }

    /**
     * Prix total de l'option boutique pour une période donnée.
     */
    public function getBoutiquePrixTotal(string $periode): int
    {
        $nbMois = match ($periode) {
            'mensuel'  => 1,
            'annuel'   => 12,
            'triennal' => 36,
            default    => 1,
        };
        return $this->getBoutiquePrixMensuel() * $nbMois;
    }
}
