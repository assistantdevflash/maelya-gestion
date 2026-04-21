<?php

namespace Tests\Unit;

use App\Models\Client;
use App\Models\User;
use App\Models\Vente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenteTest extends TestCase
{
    use RefreshDatabase;

    private function makeVente(array $attrs = []): Vente
    {
        $user = $this->creerAdmin();
        $this->actingAs($user);

        $client = Client::create([
            'prenom'    => 'Test',
            'nom'       => 'Client',
            'telephone' => '0000',
            'actif'     => true,
        ]);

        return Vente::create(array_merge([
            'client_id'    => $client->id,
            'user_id'      => $user->id,
            'total'        => 15000,
            'remise'       => 0,
            'mode_paiement'=> 'cash',
            'montant_cash' => 15000,
            'montant_mobile'=> 0,
            'montant_carte' => 0,
            'statut'       => 'validee',
        ], $attrs));
    }

    public function test_numero_est_auto_genere(): void
    {
        $vente = $this->makeVente();

        $this->assertNotNull($vente->numero);
        $this->assertStringStartsWith('V-', $vente->numero);
        $this->assertEquals(10, strlen($vente->numero)); // "V-" + 8 chars
    }

    public function test_numero_custom_est_preserve(): void
    {
        $vente = $this->makeVente(['numero' => 'V-CUSTOM01']);

        $this->assertSame('V-CUSTOM01', $vente->numero);
    }

    public function test_total_formate_attribute(): void
    {
        $vente = $this->makeVente(['total' => 25000]);

        $this->assertSame('25 000 FCFA', $vente->total_formatte);
    }

    public function test_scope_validee(): void
    {
        $user = $this->creerAdmin();
        $this->actingAs($user);

        $client = Client::create(['prenom' => 'X', 'nom' => 'Y', 'telephone' => '000', 'actif' => true]);

        Vente::create(['client_id' => $client->id, 'user_id' => $user->id,
            'total' => 1000, 'remise' => 0, 'mode_paiement' => 'cash',
            'montant_cash' => 1000, 'montant_mobile' => 0, 'montant_carte' => 0,
            'statut' => 'validee']);

        Vente::create(['client_id' => $client->id, 'user_id' => $user->id,
            'total' => 500, 'remise' => 0, 'mode_paiement' => 'cash',
            'montant_cash' => 500, 'montant_mobile' => 0, 'montant_carte' => 0,
            'statut' => 'annulee']);

        $validees = Vente::withoutGlobalScopes()->validee()->get();

        $this->assertTrue($validees->every(fn($v) => $v->statut === 'validee'));
        $this->assertEquals(1, $validees->count());
    }
}
