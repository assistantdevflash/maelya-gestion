<?php

namespace App\Models;

use App\Traits\BelongsToInstitut;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Depense extends Model
{
    use HasUuids, BelongsToInstitut, \App\Traits\Auditable, \App\Traits\EnregistreActivite;

    public function auditLabel(): string
    {
        return 'Dépense ' . ($this->libelle ?? $this->id);
    }

    protected $fillable = [
        'institut_id', 'user_id', 'description', 'categorie',
        'montant', 'date', 'justificatif', 'justificatif_fichier', 'notes',
    ];

    protected $casts = [
        'montant' => 'integer',
        'date' => 'date',
    ];

    /** Référence écrite OU pièce jointe : la dépense est défendable. */
    public function aUnJustificatif(): bool
    {
        return filled($this->justificatif) || filled($this->justificatif_fichier);
    }

    public function aUnePieceJointe(): bool
    {
        return filled($this->justificatif_fichier);
    }

    /** URL publique du reçu scanné, si présent. */
    public function getJustificatifUrlAttribute(): ?string
    {
        return $this->justificatif_fichier
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->justificatif_fichier)
            : null;
    }

    /** Vrai pour une image : on l'ouvre en aperçu, un PDF se télécharge. */
    public function justificatifEstImage(): bool
    {
        return $this->aUnePieceJointe()
            && preg_match('/\.(jpe?g|png|webp)$/i', (string) $this->justificatif_fichier) === 1;
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getMontantFormatteAttribute(): string
    {
        return number_format($this->montant, 0, ',', ' ') . ' FCFA';
    }

    public static function categorieLabel(string $categorie): string
    {
        return match($categorie) {
            'loyer' => 'Loyer',
            'salaires' => 'Salaires',
            'fournitures' => 'Fournitures',
            'produits' => 'Produits',
            'equipement' => 'Équipement',
            'marketing' => 'Marketing',
            default => 'Autres',
        };
    }
}
