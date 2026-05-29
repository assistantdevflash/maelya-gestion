<?php

namespace Tests\Unit;

use App\Models\Produit;
use PHPUnit\Framework\TestCase;

class ProduitCmpTest extends TestCase
{
    public function test_cmp_initial_egal_prix_quand_stock_zero(): void
    {
        $p = new Produit(['stock' => 0, 'cout_moyen_pondere' => 0, 'prix_achat' => 1000]);
        $this->assertSame(1200, $p->calculerNouveauCmp(10, 1200));
    }

    public function test_cmp_pondere_correctement_avec_stock_existant(): void
    {
        // Stock 10 × 1000 + entrée 10 × 2000 → CMP = 1500
        $p = new Produit(['stock' => 10, 'cout_moyen_pondere' => 1000, 'prix_achat' => 800]);
        $this->assertSame(1500, $p->calculerNouveauCmp(10, 2000));
    }

    public function test_cmp_null_si_quantite_nulle(): void
    {
        $p = new Produit(['stock' => 5, 'cout_moyen_pondere' => 1200, 'prix_achat' => 800]);
        $this->assertNull($p->calculerNouveauCmp(0, 9999));
    }

    public function test_cmp_fallback_sur_prix_achat_si_pas_de_cmp_existant(): void
    {
        // 10×500 (fallback prix_achat) + 10×1500 → 1000
        $p = new Produit(['stock' => 10, 'cout_moyen_pondere' => 0, 'prix_achat' => 500]);
        $this->assertSame(1000, $p->calculerNouveauCmp(10, 1500));
    }
}
