<?php

namespace Tests\Feature\Console;

use App\Models\Client;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientsRappelAnniversaireTest extends TestCase
{
    use RefreshDatabase;

    public function test_genere_notification_pour_anniversaires_dans_7_jours(): void
    {
        $admin = $this->creerAdmin();

        $cible = now()->addDays(7)->format('m-d');
        Client::create([
            'institut_id' => $admin->institut_id,
            'prenom' => 'Anna', 'nom' => 'Niversaire',
            'telephone' => '0600000001', 'actif' => true,
            'date_naissance' => $cible, 'points_fidelite' => 0,
        ]);
        Client::create([
            'institut_id' => $admin->institut_id,
            'prenom' => 'Autre', 'nom' => 'Date',
            'telephone' => '0600000002', 'actif' => true,
            'date_naissance' => now()->addDays(30)->format('m-d'),
            'points_fidelite' => 0,
        ]);

        $this->artisan('clients:rappel-anniversaire')->assertExitCode(0);

        $this->assertDatabaseHas('notifs', [
            'user_id' => $admin->id,
            'type'    => 'anniversaire_a_venir',
        ]);
    }
}
