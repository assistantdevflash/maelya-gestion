<?php

namespace Tests\Feature\Dashboard;

use App\Models\Avoir;
use App\Models\CodeReduction;
use App\Models\Client;
use App\Models\Vente;
use App\Services\AvoirService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvoirTest extends TestCase
{
    use RefreshDatabase;

    private function creerVente($admin, ?string $clientId = null, int $total = 20000): Vente
    {
        return Vente::create([
            'institut_id'    => $admin->institut_id,
            'client_id'      => $clientId,
            'user_id'        => $admin->id,
            'total'          => $total,
            'mode_paiement'  => 'cash',
            'montant_cash'   => $total,
            'statut'         => 'validee',
        ]);
    }

    public function test_creer_avoir_genere_code_reduction(): void
    {
        $admin = $this->creerAdmin();
        $client = Client::create([
            'institut_id' => $admin->institut_id,
            'prenom'      => 'Sophie',
            'nom'         => 'Test',
            'telephone'   => '0102030405',
            'actif'       => true,
        ]);
        $vente = $this->creerVente($admin, $client->id, 25000);

        $avoir = app(AvoirService::class)->creer([
            'institut_id' => $admin->institut_id,
            'vente_id'    => $vente->id,
            'client_id'   => $client->id,
            'user_id'     => $admin->id,
            'montant'     => 8000,
            'motif'       => 'Produit défectueux',
        ]);

        $this->assertSame('emis', $avoir->statut);
        $this->assertSame(8000, $avoir->montant);
        $this->assertMatchesRegularExpression('/^AV-\d{4}-0001$/', $avoir->numero);

        $code = CodeReduction::find($avoir->code_reduction_id);
        $this->assertNotNull($code);
        $this->assertSame('montant_fixe', $code->type);
        $this->assertSame(8000, (int) $code->valeur);
        $this->assertSame($client->id, $code->client_id);
        $this->assertStringStartsWith('AVOIR-', $code->code);
    }

    public function test_numerotation_avoir_sequentielle_par_institut(): void
    {
        $admin = $this->creerAdmin();
        $service = app(AvoirService::class);

        for ($i = 1; $i <= 3; $i++) {
            $a = $service->creer([
                'institut_id' => $admin->institut_id,
                'user_id'     => $admin->id,
                'montant'     => 1000,
            ]);
            $this->assertSame('AV-' . now()->format('Y') . '-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT), $a->numero);
        }
    }

    public function test_route_store_cree_avoir_depuis_vente(): void
    {
        $admin = $this->creerAdmin();
        $vente = $this->creerVente($admin, null, 15000);

        $this->actingAs($admin)
            ->post(route('dashboard.ventes.avoirs.store', $vente), [
                'montant' => 5000,
                'motif'   => 'Retour partiel',
            ])
            ->assertRedirect();

        $this->assertSame(1, Avoir::count());
        $this->assertSame(1, CodeReduction::where('type', 'montant_fixe')->count());
    }
}
