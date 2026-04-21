<?php

namespace Tests\Feature\Dashboard;

use App\Models\CategoriePrestation;
use App\Models\Prestation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrestationControllerTest extends TestCase
{
    use RefreshDatabase;

    private function creerCategorie(string $nom = 'Coiffure'): CategoriePrestation
    {
        return CategoriePrestation::create(['nom' => $nom, 'ordre' => 1]);
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_index_redirige_si_non_authentifie(): void
    {
        $this->get(route('dashboard.prestations.index'))
            ->assertRedirect(route('login'));
    }

    public function test_index_accessible_pour_admin(): void
    {
        $user = $this->creerAdmin();

        $this->actingAs($user)
            ->get(route('dashboard.prestations.index'))
            ->assertOk()
            ->assertViewIs('dashboard.prestations.index');
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_store_cree_une_prestation_valide(): void
    {
        $user     = $this->creerAdmin();
        $this->actingAs($user);
        $categorie = $this->creerCategorie();

        $this->post(route('dashboard.prestations.store'), [
                'categorie_prestation_id' => $categorie->id,
                'nom'                     => 'Coupe femme',
                'prix'                    => 3500,
                'duree'                   => 45,
            ])
            ->assertRedirect(route('dashboard.prestations.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('prestations', [
            'nom'        => 'Coupe femme',
            'prix'       => 3500,
            'categorie_id'=> $categorie->id,
        ]);
    }

    public function test_store_echoue_si_champs_requis_manquants(): void
    {
        $user = $this->creerAdmin();

        $this->actingAs($user)
            ->post(route('dashboard.prestations.store'), [])
            ->assertSessionHasErrors(['categorie_prestation_id', 'nom', 'prix']);
    }

    public function test_store_echoue_si_prix_negatif(): void
    {
        $user      = $this->creerAdmin();
        $this->actingAs($user);
        $categorie = $this->creerCategorie();

        $this->post(route('dashboard.prestations.store'), [
                'categorie_prestation_id' => $categorie->id,
                'nom'                     => 'Test',
                'prix'                    => -500,
            ])
            ->assertSessionHasErrors(['prix']);
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_update_modifie_la_prestation(): void
    {
        $user      = $this->creerAdmin();
        $this->actingAs($user);
        $categorie = $this->creerCategorie();

        $prestation = Prestation::create([
            'categorie_id' => $categorie->id,
            'nom'          => 'Ancien',
            'prix'         => 2000,
            'actif'        => true,
        ]);

        $this->put(route('dashboard.prestations.update', $prestation), [
                'categorie_prestation_id' => $categorie->id,
                'nom'                     => 'Nouveau Nom',
                'prix'                    => 4000,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('prestations', ['nom' => 'Nouveau Nom', 'prix' => 4000]);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_destroy_supprime_la_prestation(): void
    {
        $user      = $this->creerAdmin();
        $this->actingAs($user);
        $categorie = $this->creerCategorie();

        $prestation = Prestation::create([
            'categorie_id' => $categorie->id,
            'nom'          => 'À Supprimer',
            'prix'         => 1000,
            'actif'        => true,
        ]);

        $this->delete(route('dashboard.prestations.destroy', $prestation))
            ->assertRedirect();

        $this->assertSoftDeleted('prestations', ['id' => $prestation->id]);
    }
}
