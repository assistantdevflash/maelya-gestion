<?php

namespace Tests\Feature\Dashboard;

use App\Models\CategoriePrestation;
use App\Models\Prestation;
use App\Models\Vente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VentePourboireTest extends TestCase
{
    use RefreshDatabase;

    private function creerPrestation($admin, string $nom, int $prix): Prestation
    {
        $cat = CategoriePrestation::create([
            'institut_id' => $admin->institut_id,
            'nom'         => 'Cat-' . uniqid(),
            'ordre'       => 1,
        ]);

        return Prestation::create([
            'institut_id'  => $admin->institut_id,
            'categorie_id' => $cat->id,
            'nom'          => $nom,
            'prix'         => $prix,
            'duree'        => 30,
            'actif'        => true,
        ]);
    }

    public function test_une_vente_peut_etre_enregistree_avec_pourboire(): void
    {
        $admin = $this->creerAdmin();
        $prestation = $this->creerPrestation($admin, 'Coupe Femme', 5000);

        $panier = [[
            'type'     => 'prestation',
            'id'       => $prestation->id,
            'nom'      => $prestation->nom,
            'prix'     => 5000,
            'quantite' => 1,
        ]];

        $this->actingAs($admin)
            ->post(route('dashboard.ventes.store'), [
                'panier_json'    => json_encode($panier),
                'mode_paiement'  => 'cash',
                'total'          => 5000,
                'pourboire'      => 1500,
            ])
            ->assertRedirect();

        $vente = Vente::first();
        $this->assertNotNull($vente);
        $this->assertSame(5000, (int) $vente->total);
        $this->assertSame(1500, (int) $vente->pourboire);
    }

    public function test_pourboire_par_defaut_a_zero_si_non_fourni(): void
    {
        $admin = $this->creerAdmin();
        $prestation = $this->creerPrestation($admin, 'Manucure', 3000);

        $this->actingAs($admin)
            ->post(route('dashboard.ventes.store'), [
                'panier_json'    => json_encode([[
                    'type' => 'prestation', 'id' => $prestation->id,
                    'nom' => $prestation->nom, 'prix' => 3000, 'quantite' => 1,
                ]]),
                'mode_paiement'  => 'cash',
                'total'          => 3000,
            ])
            ->assertRedirect();

        $this->assertSame(0, (int) Vente::first()->pourboire);
    }

    public function test_pourboire_negatif_est_refuse(): void
    {
        $admin = $this->creerAdmin();
        $prestation = $this->creerPrestation($admin, 'Brushing', 2000);

        $this->actingAs($admin)
            ->post(route('dashboard.ventes.store'), [
                'panier_json'    => json_encode([[
                    'type' => 'prestation', 'id' => $prestation->id,
                    'nom' => 'Brushing', 'prix' => 2000, 'quantite' => 1,
                ]]),
                'mode_paiement'  => 'cash',
                'total'          => 2000,
                'pourboire'      => -100,
            ])
            ->assertSessionHasErrors('pourboire');
    }
}

