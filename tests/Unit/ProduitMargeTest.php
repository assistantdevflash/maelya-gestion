<?php

namespace Tests\Unit;

use App\Models\Produit;
use PHPUnit\Framework\TestCase;

class ProduitMargeTest extends TestCase
{
    public function test_marge_unitaire_utilise_cmp_quand_disponible(): void
    {
        $p = new Produit([
            'cout_moyen_pondere' => 800,
            'prix_achat'         => 1000,
            'prix_vente'         => 1500,
        ]);
        // marge = 1500 - 800 = 700 (utilise CMP, pas prix_achat)
        $this->assertSame(700, $p->marge_unitaire);
    }

    public function test_marge_unitaire_fallback_prix_achat_si_pas_de_cmp(): void
    {
        $p = new Produit([
            'cout_moyen_pondere' => 0,
            'prix_achat'         => 1000,
            'prix_vente'         => 1500,
        ]);
        $this->assertSame(500, $p->marge_unitaire);
    }

    public function test_marge_pourcent_calcule_correctement(): void
    {
        $p = new Produit([
            'cout_moyen_pondere' => 600,
            'prix_achat'         => 500,
            'prix_vente'         => 1000,
        ]);
        // (400 / 1000) * 100 = 40.0
        $this->assertSame(40.0, $p->marge_pourcent);
    }

    public function test_marge_zero_si_prix_vente_nul(): void
    {
        $p = new Produit([
            'cout_moyen_pondere' => 500,
            'prix_vente'         => 0,
        ]);
        $this->assertSame(0.0, $p->marge_pourcent);
        $this->assertSame(0, $p->marge_unitaire);
    }
}
