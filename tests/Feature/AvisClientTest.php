<?php

namespace Tests\Feature;

use App\Models\AvisClient;
use App\Models\Institut;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvisClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_formulaire_public_accessible_via_token(): void
    {
        $admin = $this->creerAdmin();
        $avis = AvisClient::create([
            'institut_id' => $admin->institut_id,
            'statut'      => 'en_attente',
        ]);
        $this->assertNotEmpty($avis->token);

        $response = $this->get(route('public.avis.show', $avis->token));
        $response->assertOk();
        $response->assertSee('Votre avis compte');
    }

    public function test_soumission_avis_enregistre_note_et_commentaire(): void
    {
        $admin = $this->creerAdmin();
        $avis = AvisClient::create([
            'institut_id' => $admin->institut_id,
            'statut'      => 'en_attente',
        ]);

        $response = $this->post(route('public.avis.submit', $avis->token), [
            'note'        => 5,
            'commentaire' => 'Super service !',
        ]);

        $response->assertOk();
        $avis->refresh();
        $this->assertSame(5, $avis->note);
        $this->assertSame('Super service !', $avis->commentaire);
        $this->assertNotNull($avis->repondu_le);
    }

    public function test_avis_approuves_affiches_sur_vitrine(): void
    {
        $admin = $this->creerAdmin();
        $institut = Institut::find($admin->institut_id);
        $institut->update(['vitrine_active' => true, 'slug' => 'mon-salon-test']);

        AvisClient::create([
            'institut_id'     => $institut->id,
            'note'            => 5,
            'commentaire'     => 'Excellent !',
            'statut'          => 'approuve',
            'client_nom_snap' => 'Marie',
            'repondu_le'      => now(),
        ]);
        AvisClient::create([
            'institut_id'     => $institut->id,
            'note'            => 1,
            'commentaire'     => 'Pas content',
            'statut'          => 'en_attente',
            'repondu_le'      => now(),
        ]);

        $response = $this->get(route('vitrine.show', $institut->slug));
        $response->assertOk();
        $response->assertSee('Excellent !');
        $response->assertDontSee('Pas content');
    }
}
