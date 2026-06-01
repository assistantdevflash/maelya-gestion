<?php

namespace Tests\Feature\Dashboard;

use App\Models\CategoriePrestation;
use App\Models\Prestation;
use App\Models\Vente;
use App\Services\FactureNumeroService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactureNumeroTest extends TestCase
{
    use RefreshDatabase;

    private function creerPrestation($admin, int $prix = 5000): Prestation
    {
        $cat = CategoriePrestation::create([
            'institut_id' => $admin->institut_id,
            'nom'         => 'Cat-' . uniqid(),
            'ordre'       => 1,
        ]);

        return Prestation::create([
            'institut_id'  => $admin->institut_id,
            'categorie_id' => $cat->id,
            'nom'          => 'Prestation Test',
            'prix'         => $prix,
            'duree'        => 30,
            'actif'        => true,
        ]);
    }

    private function postVente($admin, Prestation $prestation, int $prix = 5000): void
    {
        $this->actingAs($admin)
            ->post(route('dashboard.ventes.store'), [
                'panier_json' => json_encode([[
                    'type' => 'prestation', 'id' => $prestation->id,
                    'nom' => $prestation->nom, 'prix' => $prix, 'quantite' => 1,
                ]]),
                'mode_paiement' => 'cash',
                'total'         => $prix,
            ])
            ->assertRedirect();
    }

    public function test_facture_est_numerotee_au_format_FAC_annee_seq(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 6, 1, 10));

        $admin = $this->creerAdmin();
        $prestation = $this->creerPrestation($admin);

        $this->postVente($admin, $prestation);
        $this->postVente($admin, $prestation);
        $this->postVente($admin, $prestation);

        $numeros = Vente::orderBy('created_at')->pluck('numero_facture')->all();

        $this->assertSame(['FAC-2026-0001', 'FAC-2026-0002', 'FAC-2026-0003'], $numeros);

        Carbon::setTestNow();
    }

    public function test_sequence_est_scopee_par_institut(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 6, 1, 10));

        $admin1 = $this->creerAdmin();
        $admin2 = $this->creerAdmin();
        $prestation1 = $this->creerPrestation($admin1);
        $prestation2 = $this->creerPrestation($admin2);

        $this->postVente($admin1, $prestation1);
        $this->postVente($admin1, $prestation1);
        $this->postVente($admin2, $prestation2);

        $venteA = Vente::withoutGlobalScopes()->where('institut_id', $admin1->institut_id)->orderBy('created_at')->get();
        $venteB = Vente::withoutGlobalScopes()->where('institut_id', $admin2->institut_id)->orderBy('created_at')->get();

        $this->assertSame(['FAC-2026-0001', 'FAC-2026-0002'], $venteA->pluck('numero_facture')->all());
        $this->assertSame(['FAC-2026-0001'], $venteB->pluck('numero_facture')->all());

        Carbon::setTestNow();
    }

    public function test_sequence_est_remise_a_zero_chaque_annee(): void
    {
        $admin = $this->creerAdmin();
        $service = app(FactureNumeroService::class);

        // Simuler une facture existante en 2025
        Vente::withoutGlobalScopes()->create([
            'institut_id'    => $admin->institut_id,
            'user_id'        => $admin->id,
            'numero'         => 'V-OLD2025A',
            'numero_facture' => 'FAC-2025-0042',
            'total'          => 1000,
            'mode_paiement'  => 'cash',
            'montant_cash'   => 1000,
            'statut'         => 'validee',
        ]);

        $numero2026 = $service->generate($admin->institut_id, Carbon::create(2026, 1, 15));
        $numero2025 = $service->generate($admin->institut_id, Carbon::create(2025, 12, 31));

        $this->assertSame('FAC-2026-0001', $numero2026);
        $this->assertSame('FAC-2025-0043', $numero2025);
    }

    public function test_facture_pdf_est_telechargeable(): void
    {
        $admin = $this->creerAdmin();
        // Passer sur un plan premium pour activer caisse_impression
        $premium = \App\Models\PlanAbonnement::create([
            'nom' => 'Premium', 'slug' => 'premium', 'duree_type' => 'mensuel',
            'duree_jours' => 30, 'prix' => 10000, 'actif' => true,
        ]);
        $admin->abonnements()->update(['plan_id' => $premium->id]);

        $prestation = $this->creerPrestation($admin);

        $this->postVente($admin, $prestation);
        $vente = Vente::first();

        $response = $this->actingAs($admin)->get(route('dashboard.ventes.facture-pdf', $vente));
        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertNotNull($vente->fresh()->numero_facture);
    }
}
