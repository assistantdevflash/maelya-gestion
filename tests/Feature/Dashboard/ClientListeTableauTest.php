<?php

namespace Tests\Feature\Dashboard;

use App\Models\Client;
use App\Models\Vente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Liste des clients : présentation par défaut et contenu des colonnes.
 *
 * - la vue tableau est affichée par défaut
 * - la colonne « Client » rappelle la date de la dernière visite
 * - la colonne « Contact » regroupe téléphone et e-mail
 */
class ClientListeTableauTest extends TestCase
{
    use RefreshDatabase;

    private function client(array $attributs = []): Client
    {
        $user = $this->creerAdmin();

        return Client::create(array_merge([
            'institut_id'   => $user->institut_id,
            'prenom'        => 'Awa',
            'nom'           => 'Traoré',
            'telephone'     => '0701020304',
            'email'         => 'awa.traore@example.com',
            'actif'         => true,
            'points_fidelite' => 0,
        ], $attributs));
    }

    private function vente(Client $client, string $date): Vente
    {
        $vente = Vente::create([
            'institut_id'   => $client->institut_id,
            'client_id'     => $client->id,
            'user_id'       => \App\Models\User::where('institut_id', $client->institut_id)->value('id'),
            'total'         => 12000,
            'mode_paiement' => 'cash',
            'montant_cash'  => 12000,
            'statut'        => 'validee',
        ]);

        $vente->forceFill(['created_at' => Carbon::parse($date)])->save();

        return $vente;
    }

    public function test_la_vue_tableau_est_affichee_par_defaut(): void
    {
        $client = $this->client();

        $html = $this->actingAs(\App\Models\User::where('institut_id', $client->institut_id)->firstOrFail())
            ->get(route('dashboard.clients.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString("viewMode: 'table'", $html);
    }

    public function test_la_colonne_contact_regroupe_telephone_et_email(): void
    {
        $client = $this->client();
        $user   = \App\Models\User::where('institut_id', $client->institut_id)->firstOrFail();

        $html = $this->actingAs($user)
            ->get(route('dashboard.clients.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('>Contact<', $html);
        $this->assertStringNotContainsString('>Téléphone<', $html);
        $this->assertStringContainsString('0701020304', $html);
        $this->assertStringContainsString('awa.traore@example.com', $html);
    }

    public function test_la_colonne_client_affiche_la_date_de_derniere_visite(): void
    {
        $client = $this->client();
        $vente  = $this->vente($client, '2026-10-12 09:30:00');
        $user   = \App\Models\User::where('institut_id', $client->institut_id)->firstOrFail();

        $attendu = Carbon::parse('2026-10-12 09:30:00')->translatedFormat('d M Y');

        $html = $this->actingAs($user)
            ->get(route('dashboard.clients.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Dernière visite', $html);
        $this->assertStringContainsString($attendu, $html);
    }

    public function test_un_client_sans_vente_indique_aucune_visite(): void
    {
        $client = $this->client(['prenom' => 'Sans', 'nom' => 'Visite', 'telephone' => '0709999999', 'email' => null]);
        $user   = \App\Models\User::where('institut_id', $client->institut_id)->firstOrFail();

        $this->actingAs($user)
            ->get(route('dashboard.clients.index'))
            ->assertOk()
            ->assertSee('Aucune visite');
    }

    public function test_la_derniere_visite_est_une_date_exploitable(): void
    {
        $client = $this->client();
        $this->vente($client, '2026-09-01 14:00:00');

        $client = Client::query()
            ->withMax(['ventes as derniere_vente_le' => fn ($q) => $q->where('statut', 'validee')], 'created_at')
            ->findOrFail($client->id);

        $this->assertInstanceOf(Carbon::class, $client->derniere_visite);
        $this->assertSame('2026-09-01 14:00:00', $client->derniere_visite->toDateTimeString());
    }
}
