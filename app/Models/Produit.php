<?php

namespace App\Models;

use App\Traits\BelongsToInstitut;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Produit extends Model
{
    use HasUuids, SoftDeletes, BelongsToInstitut, \App\Traits\Auditable;

    /** @var array Colonnes à ignorer dans l'audit (stock change tracé par MouvementStock) */
    protected array $auditExclude = ['stock'];

    public function auditLabel(): string
    {
        return 'Produit ' . ($this->nom ?? $this->id);
    }

    protected $fillable = [
        'institut_id', 'categorie_id', 'nom', 'reference', 'code_barre',
        'prix_achat', 'cout_moyen_pondere', 'prix_vente', 'prix_promo', 'stock', 'seuil_alerte', 'unite',
        'description', 'description_courte', 'photo', 'actif', 'visible_boutique', 'featured',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'visible_boutique' => 'boolean',
        'featured' => 'boolean',
        'prix_achat' => 'integer',
        'cout_moyen_pondere' => 'integer',
        'prix_vente' => 'integer',
        'prix_promo' => 'integer',
        'stock' => 'integer',
        'seuil_alerte' => 'integer',
    ];

    /**
     * Met à jour le CMP suite à une entrée de stock.
     * Formule : ((stock_actuel × CMP) + (quantite_entrée × prix_unitaire)) / (stock_actuel + quantite_entrée)
     */
    public function recalculerCmp(int $quantiteEntree, int $prixUnitaire): void
    {
        $nouveauCmp = $this->calculerNouveauCmp($quantiteEntree, $prixUnitaire);
        if ($nouveauCmp === null) return;

        $this->cout_moyen_pondere = $nouveauCmp;
        $this->save();
    }

    /** Calcul pur (sans I/O) — exposé pour les tests unitaires. */
    public function calculerNouveauCmp(int $quantiteEntree, int $prixUnitaire): ?int
    {
        $stockAvant = (int) $this->stock;
        $cmpAvant   = (int) ($this->cout_moyen_pondere ?: $this->prix_achat);
        $nouveauStock = $stockAvant + $quantiteEntree;

        if ($nouveauStock <= 0 || $quantiteEntree <= 0) {
            return null;
        }

        return (int) round(
            (($stockAvant * $cmpAvant) + ($quantiteEntree * $prixUnitaire)) / $nouveauStock
        );
    }

    /** Marge unitaire en FCFA (prix vente - CMP, fallback prix achat) */
    public function getMargeUnitaireAttribute(): int
    {
        $cout = $this->cout_moyen_pondere ?: $this->prix_achat;
        return max(0, $this->prix_vente - $cout);
    }

    /** Marge % */
    public function getMargePourcentAttribute(): float
    {
        if ($this->prix_vente <= 0) return 0;
        return round(($this->marge_unitaire / $this->prix_vente) * 100, 1);
    }

    public function categorie()
    {
        return $this->belongsTo(CategorieProduit::class, 'categorie_id');
    }

    public function images()
    {
        return $this->hasMany(ProduitImage::class, 'produit_id')->orderBy('ordre');
    }

    public function imagePrincipale()
    {
        return $this->hasOne(ProduitImage::class, 'produit_id')->where('is_principale', true)->orderBy('ordre');
    }

    /** Retourne l'URL de la photo principale (images table prioritaire, fallback sur photo colonne) */
    public function getPhotoUrlAttribute(): ?string
    {
        $img = $this->imagePrincipale;
        if ($img) return asset('storage/' . $img->chemin);
        if ($this->photo) return asset('storage/' . $this->photo);
        return null;
    }

    public function mouvements()
    {
        return $this->hasMany(MouvementStock::class, 'produit_id');
    }

    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    public function scopeEnAlerte($query)
    {
        return $query->whereColumn('stock', '<=', 'seuil_alerte');
    }

    public function isEnAlerte(): bool
    {
        return $this->stock <= $this->seuil_alerte;
    }

    public function getPrixVenteFormatteAttribute(): string
    {
        return number_format($this->prix_vente, 0, ',', ' ') . ' FCFA';
    }
}
