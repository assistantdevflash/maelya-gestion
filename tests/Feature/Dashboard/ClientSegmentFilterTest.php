<?php

namespace Tests\Feature\Dashboard;

use App\Models\Client;
use App\Models\Vente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientSegmentFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_filtre_segment_vip_retourne_clients_avec_10_ventes(): void
    {
        $admin = $this->creerAdmin(); $p = \App\Models\PlanAbonnement::create(['nom'=>'P','slug'=>'premium','duree_type'=>'mensuel','duree_jours'=>30,'prix'=>1,'actif'=>true]); $admin->abonnements()->update(['plan_id'=>$p->id]);
        $vip = Client::create([
            'institut_id' => $admin->institut_id,
            'prenom' => 'Vipa', 'nom' => 'Top', 'telephone' => '0600000' . random_int(100,999), 'actif' => true, 'points_fidelite' => 0,
        ]);
        $petit = Client::create([
            'institut_id' => $admin->institut_id,
            'prenom' => 'Petit', 'nom' => 'Client', 'telephone' => '0600000' . random_int(100,999), 'actif' => true, 'points_fidelite' => 0,
        ]);

        for ($i = 0; $i < 10; $i++) {
            Vente::create([
                'institut_id' => $admin->institut_id, 'client_id' => $vip->id,
                'user_id' => $admin->id, 'total' => 1000,
                'mode_paiement' => 'cash', 'montant_cash' => 1000, 'statut' => 'validee',
            ]);
        }

        $response = $this->actingAs($admin)->get(route('dashboard.clients.index', ['segment' => 'vip']));
        $response->assertOk();
        $response->assertSee('Vipa');
        $response->assertDontSee('Petit Client');
    }

    public function test_filtre_points_min(): void
    {
        $admin = $this->creerAdmin(); $p = \App\Models\PlanAbonnement::create(['nom'=>'P','slug'=>'premium','duree_type'=>'mensuel','duree_jours'=>30,'prix'=>1,'actif'=>true]); $admin->abonnements()->update(['plan_id'=>$p->id]);
        Client::create([
            'institut_id' => $admin->institut_id,
            'prenom' => 'Riche', 'nom' => 'Points', 'telephone' => '0600000' . random_int(100,999), 'actif' => true, 'points_fidelite' => 500,
        ]);
        Client::create([
            'institut_id' => $admin->institut_id,
            'prenom' => 'Pauvre', 'nom' => 'Points', 'telephone' => '0600000' . random_int(100,999), 'actif' => true, 'points_fidelite' => 5,
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard.clients.index', ['points_min' => 100]));
        $response->assertOk();
        $response->assertSee('Riche');
        $response->assertDontSee('Pauvre Points');
    }

    public function test_filtre_mois_anniversaire(): void
    {
        $admin = $this->creerAdmin(); $p = \App\Models\PlanAbonnement::create(['nom'=>'P','slug'=>'premium','duree_type'=>'mensuel','duree_jours'=>30,'prix'=>1,'actif'=>true]); $admin->abonnements()->update(['plan_id'=>$p->id]);
        Client::create([
            'institut_id' => $admin->institut_id,
            'prenom' => 'Mars', 'nom' => 'Birthday', 'telephone' => '0600000' . random_int(100,999), 'actif' => true,
            'date_naissance' => '03-15', 'points_fidelite' => 0,
        ]);
        Client::create([
            'institut_id' => $admin->institut_id,
            'prenom' => 'Juin', 'nom' => 'Birthday', 'telephone' => '0600000' . random_int(100,999), 'actif' => true,
            'date_naissance' => '06-15', 'points_fidelite' => 0,
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard.clients.index', ['mois_anniv' => '03']));
        $response->assertOk();
        $response->assertSee('Mars');
        $response->assertDontSee('Juin Birthday');
    }
}
