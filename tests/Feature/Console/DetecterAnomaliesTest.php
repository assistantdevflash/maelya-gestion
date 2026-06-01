<?php

namespace Tests\Feature\Console;

use App\Models\Produit;
use App\Models\CategorieProduit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DetecterAnomaliesTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifie_admin_si_produit_en_rupture(): void
    {
        $admin = $this->creerAdmin();
        $cat = CategorieProduit::create(['institut_id' => $admin->institut_id, 'nom' => 'Cat']);
        Produit::create([
            'institut_id' => $admin->institut_id, 'categorie_id' => $cat->id,
            'nom' => 'Vide', 'prix_achat' => 100, 'prix_vente' => 200,
            'stock' => 0, 'seuil_alerte' => 5, 'unite' => 'pièce', 'actif' => true,
        ]);

        $this->artisan('maelya:anomalies')->assertExitCode(0);

        $this->assertDatabaseHas('notifs', [
            'user_id' => $admin->id,
            'type'    => 'anomalie_stock',
        ]);
    }
}
