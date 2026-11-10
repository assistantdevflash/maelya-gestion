<?php

namespace Tests\Feature\Dashboard;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Le sélecteur de client de la caisse ne doit plus être limité aux 200
 * premiers clients de l'établissement.
 */
class ClientRechercheJsonTest extends TestCase
{
    use RefreshDatabase;

    private function creerClient(string $institutId, string $prenom, string $nom): Client
    {
        return Client::create([
            'institut_id' => $institutId,
            'type_client' => 'personne_physique',
            'prenom'      => $prenom,
            'nom'         => $nom,
            'telephone'   => '07' . random_int(10000000, 99999999),
            'actif'       => true,
        ]);
    }

    public function test_un_client_au_dela_de_la_liste_initiale_reste_trouvable(): void
    {
        $admin = $this->creerAdmin();

        // Bruit : 205 clients « A… » qui occupent le début de l'alphabet.
        $lignes = [];
        for ($i = 1; $i <= 205; $i++) {
            $lignes[] = [
                'id'               => (string) Str::uuid(),
                'institut_id'      => $admin->institut_id,
                'type_client'      => 'personne_physique',
                'prenom'           => 'Client',
                'nom'              => 'Alpha' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
                'telephone'        => '07000000' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                'actif'            => true,
                'points_fidelite'  => 0,
                'fidelite_token'   => Str::random(40),
                'created_at'       => now(),
                'updated_at'       => now(),
            ];
        }
        Client::insert($lignes);

        $cible = $this->creerClient($admin->institut_id, 'Zebulon', 'Farfelu');

        $reponse = $this->actingAs($admin)
            ->getJson(route('dashboard.clients.recherche-json', ['q' => 'Zebulon']));

        $reponse->assertOk();
        $ids = collect($reponse->json())->pluck('id')->all();
        $this->assertContains($cible->id, $ids, 'Le client doit être trouvé par la recherche serveur.');
    }

    public function test_la_recherche_est_cloisonnee_par_etablissement(): void
    {
        $admin = $this->creerAdmin();
        $autre = $this->creerAdmin();

        $this->creerClient($autre->institut_id, 'Zebulon', 'Ailleurs');

        $reponse = $this->actingAs($admin)
            ->getJson(route('dashboard.clients.recherche-json', ['q' => 'Zebulon']));

        $reponse->assertOk();
        $this->assertCount(0, $reponse->json(), 'Aucun client d\'un autre établissement ne doit fuiter.');
    }

    public function test_la_recherche_accepte_le_telephone_et_la_raison_sociale(): void
    {
        $admin = $this->creerAdmin();

        $tel = $this->creerClient($admin->institut_id, 'Awa', 'Koné');
        $tel->update(['telephone' => '0102030405']);

        $societe = Client::create([
            'institut_id'   => $admin->institut_id,
            'type_client'   => 'entreprise',
            'raison_sociale' => 'Choco Ivoire SARL',
            'telephone'     => '0506070809',
            'actif'         => true,
        ]);

        $parTel = $this->actingAs($admin)
            ->getJson(route('dashboard.clients.recherche-json', ['q' => '0102030405']));
        $parTel->assertOk();
        $this->assertContains($tel->id, collect($parTel->json())->pluck('id')->all());

        $parRaison = $this->actingAs($admin)
            ->getJson(route('dashboard.clients.recherche-json', ['q' => 'Choco Ivoire']));
        $parRaison->assertOk();
        $this->assertContains($societe->id, collect($parRaison->json())->pluck('id')->all());
    }
}
