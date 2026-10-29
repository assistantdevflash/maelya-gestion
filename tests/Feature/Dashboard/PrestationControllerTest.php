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

    public function test_index_affiche_les_compteurs_de_statistiques(): void
    {
        $user = $this->creerAdmin();
        $this->actingAs($user); // nécessaire pour l'auto-remplissage de institut_id

        $cat1 = $this->creerCategorie('Coiffure');
        $this->creerCategorie('Soins');

        foreach ([['Coupe', true], ['Brushing', true], ['Coloration', false]] as [$nom, $actif]) {
            Prestation::create([
                'institut_id'  => $user->institut_id,
                'categorie_id' => $cat1->id,
                'nom'          => $nom,
                'prix'         => 5000,
                'duree'        => 30,
                'actif'        => $actif,
            ]);
        }

        $response = $this->actingAs($user)->get(route('dashboard.prestations.index'));

        $response->assertOk()
            ->assertSee('Total prestations')
            ->assertSee('Total catégories')
            ->assertSee('Prestations actives')
            ->assertSee('Prestations inactives')
            ->assertViewHas('stats', fn ($stats) => $stats === [
                'prestations' => 3,
                'categories'  => 2,
                'actives'     => 2,
                'inactives'   => 1,
            ]);
    }

    public function test_les_compteurs_ignorent_les_prestations_des_autres_instituts(): void
    {
        $user  = $this->creerAdmin();
        $autre = $this->creerAdmin(); // autre établissement

        $this->actingAs($user);
        $cat = $this->creerCategorie('Coiffure');

        Prestation::create([
            'institut_id'  => $user->institut_id,
            'categorie_id' => $cat->id,
            'nom'          => 'Coupe',
            'prix'         => 5000,
            'duree'        => 30,
            'actif'        => true,
        ]);

        Prestation::create([
            'institut_id'  => $autre->institut_id,
            'categorie_id' => $cat->id,
            'nom'          => 'Prestation autre institut',
            'prix'         => 9000,
            'duree'        => 60,
            'actif'        => false,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard.prestations.index'))
            ->assertOk()
            ->assertViewHas('stats', fn ($stats) => $stats['prestations'] === 1
                && $stats['actives'] === 1
                && $stats['inactives'] === 0);
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
