<?php

namespace Tests\Unit;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    private function makeClient(array $attrs = []): Client
    {
        $user    = $this->creerAdmin();
        $this->actingAs($user);

        return Client::create(array_merge([
            'prenom'    => 'Awa',
            'nom'       => 'Koné',
            'telephone' => '0707070707',
            'actif'     => true,
        ], $attrs));
    }

    public function test_nom_complet_attribute(): void
    {
        $client = $this->makeClient(['prenom' => 'Awa', 'nom' => 'Koné']);

        $this->assertSame('Awa Koné', $client->nom_complet);
    }

    public function test_is_anniversaire_retourne_vrai_si_date_est_aujourd_hui(): void
    {
        $today  = now()->format('m-d');
        $client = $this->makeClient(['date_naissance' => $today]);

        $this->assertTrue($client->isAnniversaire());
    }

    public function test_is_anniversaire_retourne_faux_si_date_differente(): void
    {
        $autreJour = now()->subDay()->format('m-d');
        $client    = $this->makeClient(['date_naissance' => $autreJour]);

        $this->assertFalse($client->isAnniversaire());
    }

    public function test_is_anniversaire_retourne_faux_si_date_nulle(): void
    {
        $client = $this->makeClient(['date_naissance' => null]);

        $this->assertFalse($client->isAnniversaire());
    }

    public function test_naissance_formatee_attribute(): void
    {
        $client = $this->makeClient(['date_naissance' => '04-15']);

        $this->assertSame('15 avril', $client->naissance_formatee);
    }

    public function test_naissance_formatee_nulle_si_pas_de_date(): void
    {
        $client = $this->makeClient(['date_naissance' => null]);

        $this->assertNull($client->naissance_formatee);
    }

    public function test_scope_actif(): void
    {
        $user = $this->creerAdmin();
        $this->actingAs($user);

        Client::create(['prenom' => 'Active', 'nom' => 'Client', 'telephone' => '1111', 'actif' => true]);
        Client::create(['prenom' => 'Inactif', 'nom' => 'Client', 'telephone' => '2222', 'actif' => false]);

        // withoutGlobalScopes pour ignorer le filtre institut dans ce scope-test
        $actifs = Client::withoutGlobalScopes()->actif()->get();

        $this->assertTrue($actifs->every(fn($c) => $c->actif));
        $this->assertFalse($actifs->contains('prenom', 'Inactif'));
    }
}
