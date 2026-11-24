<?php

namespace Tests\Feature\Dashboard;

use App\Models\Client;
use App\Models\Credit;
use App\Models\Institut;
use App\Models\User;
use App\Services\CreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Titre libre d'un crédit, sur le modèle de celui des devis et des factures.
 *
 * Facultatif : un crédit sans titre doit continuer de fonctionner exactement
 * comme avant.
 */
class CreditTitreTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Institut $institut;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->creerAdmin(['role' => 'admin'], ['credit_plafond_defaut' => 100000]);
        $this->institut = Institut::findOrFail($this->user->institut_id);

        $this->client = Client::create([
            'institut_id' => $this->institut->id,
            'type_client' => 'personne_physique',
            'prenom'      => 'Awa',
            'nom'         => 'Kone',
            'telephone'   => '0701020304',
        ]);
    }

    private function payload(array $surcharges = []): array
    {
        return array_merge([
            'client_id'      => $this->client->id,
            'lignes'         => json_encode([
                ['designation' => 'Prestation', 'quantite' => 1, 'prix_unitaire' => 60000],
            ]),
            'apport_initial' => 0,
            'nb_echeances'   => 3,
            'frequence'      => 'mensuelle',
        ], $surcharges);
    }

    public function test_le_titre_est_enregistre(): void
    {
        $this->actingAs($this->user)
            ->post(route('dashboard.credits.store'), $this->payload(['titre' => 'Pack mariage']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Pack mariage', Credit::firstOrFail()->titre);
    }

    public function test_le_titre_est_facultatif(): void
    {
        $this->actingAs($this->user)
            ->post(route('dashboard.credits.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $credit = Credit::firstOrFail();

        $this->assertNull($credit->titre);
        $this->assertSame(60000, $credit->montant_total);
    }

    public function test_le_titre_est_nettoye_du_html(): void
    {
        $this->actingAs($this->user)
            ->post(route('dashboard.credits.store'), $this->payload([
                'titre' => '<script>alert(1)</script>Pack mariage',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame('alert(1)Pack mariage', Credit::firstOrFail()->titre);
    }

    public function test_un_titre_trop_long_est_refuse(): void
    {
        $this->actingAs($this->user)
            ->post(route('dashboard.credits.store'), $this->payload(['titre' => str_repeat('a', 201)]))
            ->assertSessionHasErrors('titre');

        $this->assertSame(0, Credit::count());
    }

    public function test_le_titre_apparait_dans_la_liste_et_sur_la_fiche(): void
    {
        $this->actingAs($this->user)->post(route('dashboard.credits.store'), $this->payload(['titre' => 'Pack mariage']));
        $credit = Credit::firstOrFail();

        $this->actingAs($this->user)
            ->get(route('dashboard.credits.index'))
            ->assertOk()
            ->assertSee('Pack mariage');

        $this->actingAs($this->user)
            ->get(route('dashboard.credits.show', $credit))
            ->assertOk()
            ->assertSee('Pack mariage');
    }

    public function test_le_titre_peut_etre_modifie(): void
    {
        $this->actingAs($this->user)->post(route('dashboard.credits.store'), $this->payload(['titre' => 'Ancien titre']));
        $credit = Credit::firstOrFail();

        $this->actingAs($this->user)
            ->put(route('dashboard.credits.update', $credit), $this->payload(['titre' => 'Nouveau titre']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Nouveau titre', $credit->fresh()->titre);
    }

    public function test_le_titre_apparait_sur_la_page_de_suivi_du_client(): void
    {
        $this->actingAs($this->user)->post(route('dashboard.credits.store'), $this->payload(['titre' => 'Pack mariage']));
        $credit = Credit::firstOrFail();

        $this->get(app(CreditService::class)->urlSuivi($credit))
            ->assertOk()
            ->assertSee('Pack mariage');
    }

    public function test_le_message_whatsapp_cite_le_titre(): void
    {
        $this->actingAs($this->user)->post(route('dashboard.credits.store'), $this->payload(['titre' => 'Pack mariage']));
        $credit = Credit::firstOrFail()->load(['client', 'echeances']);

        $message = app(CreditService::class)->messageRelance($credit, 'fiche');

        $this->assertStringContainsString('« Pack mariage »', $message);
    }

    public function test_le_message_whatsapp_reste_lisible_sans_titre(): void
    {
        $this->actingAs($this->user)->post(route('dashboard.credits.store'), $this->payload());
        $credit = Credit::firstOrFail()->load(['client', 'echeances']);

        $message = app(CreditService::class)->messageRelance($credit, 'fiche');

        $this->assertStringContainsString('le détail de votre crédit en cours', $message);
        $this->assertStringNotContainsString('«', $message);
    }
}
