<?php

namespace Tests\Feature\Commercial;

use App\Models\CommercialProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * La page « Notifications » est commune à tous les rôles : elle doit adopter
 * le layout de l'espace où se trouve l'utilisateur.
 */
class NotificationsLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_commercial_voit_la_page_dans_le_layout_commercial(): void
    {
        $user = User::factory()->create([
            'prenom'      => 'Awa',
            'nom_famille' => 'Diomandé',
            'role'        => 'commercial',
            'actif'       => true,
        ]);

        CommercialProfile::create([
            'user_id' => $user->id,
            'code'    => strtoupper(Str::random(6)),
        ]);

        $html = $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->getContent();

        // Marqueurs du layout commercial
        $this->assertStringContainsString('Guide Porte-à-Porte', $html);
        $this->assertStringContainsString('Mes parrainages', $html);

        // La barre de recherche globale de l'espace établissement ne doit pas
        // apparaître.
        $this->assertStringNotContainsString('Rechercher client, vente, devis', $html);
    }

    public function test_un_administrateur_garde_le_layout_du_tableau_de_bord(): void
    {
        $admin = $this->creerAdmin();

        $html = $this->actingAs($admin)
            ->get(route('notifications.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Rechercher client, vente, devis', $html);
        $this->assertStringNotContainsString('Guide Porte-à-Porte', $html);
    }
}
