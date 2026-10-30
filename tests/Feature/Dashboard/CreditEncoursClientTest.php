<?php

namespace Tests\Feature\Dashboard;

use App\Models\Client;
use App\Models\Credit;
use App\Models\Echeance;
use App\Models\User;
use App\Models\Vente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Alerte « ce client a déjà un encours » sur le formulaire de création
 * d'un crédit : endpoint consommé en AJAX après sélection du client.
 */
class CreditEncoursClientTest extends TestCase
{
    use RefreshDatabase;

    private function creerClient(User $admin, string $prenom = 'Basile'): Client
    {
        $this->actingAs($admin);

        return Client::create([
            'institut_id' => $admin->institut_id,
            'prenom'      => $prenom,
            'nom'         => 'Tie',
            'telephone'   => '0748606366',
            'actif'       => true,
        ]);
    }

    /**
     * Un crédit est toujours rattaché à une vente à crédit (vente_id non nul).
     */
    private function creerCredit(User $admin, Client $client, array $attrs = []): Credit
    {
        $this->actingAs($admin);

        $vente = Vente::create([
            'institut_id'   => $admin->institut_id,
            'user_id'       => $admin->id,
            'numero'        => 'V-' . uniqid(),
            'total'         => 30000,
            'montant_paye'  => 0,
            'mode_paiement' => 'cash',
            'credit_statut' => 'en_cours',
            'statut'        => 'validee',
        ]);

        return Credit::create(array_merge([
            'institut_id'     => $admin->institut_id,
            'client_id'       => $client->id,
            'vente_id'        => $vente->id,
            'montant_total'   => 30000,
            'apport_initial'  => 0,
            'reste_a_payer'   => 20000,
            'nb_echeances'    => 3,
            'frequence'       => 'mensuelle',
            'statut'          => 'en_cours',
            'date_debut'      => now()->subMonth()->toDateString(),
            'date_fin_prevue' => now()->addMonths(2)->toDateString(),
        ], $attrs));
    }

    public function test_retourne_les_credits_non_soldes_du_client(): void
    {
        $admin  = $this->creerAdmin();
        $client = $this->creerClient($admin);

        $this->creerCredit($admin, $client, ['statut' => 'en_cours', 'reste_a_payer' => 20000]);
        $this->creerCredit($admin, $client, ['statut' => 'retard', 'reste_a_payer' => 5000]);
        // Soldé : ne doit pas apparaître
        $this->creerCredit($admin, $client, ['statut' => 'solde', 'reste_a_payer' => 0]);

        $response = $this->actingAs($admin)
            ->getJson(route('dashboard.credits.client-encours', $client));

        $response->assertOk()
            ->assertJsonPath('nb', 2)
            ->assertJsonPath('nb_retard', 1)
            ->assertJsonPath('total_du', 25000)
            ->assertJsonPath('total_du_label', '25 000 F');

        $statuts = collect($response->json('credits'))->pluck('statut')->sort()->values()->all();
        $this->assertSame(['en_cours', 'retard'], $statuts);
    }

    public function test_les_lignes_contiennent_libelle_reste_et_lien(): void
    {
        $admin  = $this->creerAdmin();
        $client = $this->creerClient($admin);
        $credit = $this->creerCredit($admin, $client, [
            'statut'        => 'retard',
            'reste_a_payer' => 12500,
        ]);

        Echeance::create([
            'credit_id'    => $credit->id,
            'institut_id'  => $admin->institut_id,
            'numero'       => 1,
            'date_prevue'  => now()->subDays(10)->toDateString(),
            'montant'      => 12500,
            'montant_paye' => 0,
            'statut'       => 'retard',
        ]);

        $ligne = $this->actingAs($admin)
            ->getJson(route('dashboard.credits.client-encours', $client))
            ->assertOk()
            ->json('credits.0');

        $this->assertSame('En retard', $ligne['statut_label']);
        $this->assertSame('12 500 F', $ligne['reste_label']);
        $this->assertSame(route('dashboard.credits.show', $credit), $ligne['url']);
        $this->assertStringContainsString('1 échéance en retard', $ligne['details']);
    }

    public function test_retourne_vide_si_aucun_encours(): void
    {
        $admin  = $this->creerAdmin();
        $client = $this->creerClient($admin);

        $this->actingAs($admin)
            ->getJson(route('dashboard.credits.client-encours', $client))
            ->assertOk()
            ->assertJsonPath('nb', 0)
            ->assertJsonPath('credits', []);
    }

    public function test_refuse_un_client_d_un_autre_institut(): void
    {
        $admin  = $this->creerAdmin();
        $autre  = $this->creerAdmin();
        $client = $this->creerClient($autre, 'Autre');

        $this->actingAs($admin)
            ->getJson(route('dashboard.credits.client-encours', $client))
            ->assertNotFound();
    }
}
