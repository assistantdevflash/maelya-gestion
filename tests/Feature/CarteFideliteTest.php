<?php

namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarteFideliteTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_genere_a_la_creation_du_client(): void
    {
        $admin = $this->creerAdmin();
        $client = Client::create([
            'institut_id' => $admin->institut_id,
            'prenom' => 'Token', 'nom' => 'Auto',
            'telephone' => '0600000001', 'actif' => true, 'points_fidelite' => 0,
        ]);
        $this->assertNotEmpty($client->fidelite_token);
    }

    public function test_page_carte_fidelite_publique_affiche_solde(): void
    {
        $admin = $this->creerAdmin();
        $client = Client::create([
            'institut_id' => $admin->institut_id,
            'prenom' => 'Eva', 'nom' => 'Fidele',
            'telephone' => '0600000002', 'actif' => true, 'points_fidelite' => 42,
        ]);
        $response = $this->get(route('public.carte-fidelite', $client->fidelite_token));
        $response->assertOk();
        $response->assertSee('Eva');
        $response->assertSee('42');
    }
}
