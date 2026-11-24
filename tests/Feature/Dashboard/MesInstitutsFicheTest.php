<?php

namespace Tests\Feature\Dashboard;

use App\Models\Client;
use App\Models\EtablissementType;
use App\Models\Institut;
use App\Models\User;
use App\Models\Vente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fiche détaillée d'un établissement (lecture seule).
 *
 * Le point délicat : le scope multi-tenant filtre sur l'établissement
 * *courant*. La fiche doit pourtant afficher les chiffres de l'établissement
 * demandé, même quand on en gère un autre. D'où le `withoutGlobalScopes()`
 * couplé à un `where('institut_id', ...)` explicite dans le contrôleur.
 */
class MesInstitutsFicheTest extends TestCase
{
    use RefreshDatabase;

    private function contexte(): array
    {
        EtablissementType::create([
            'code'     => 'salon_coiffure',
            'libelle'  => 'Salon de coiffure',
            'actif'    => true,
            'position' => 1,
        ]);

        $admin = $this->creerAdmin(['role' => 'admin']);
        $principal = Institut::find($admin->institut_id);

        // Second établissement du même propriétaire, avec ses propres données.
        $secondaire = $this->creerInstitut([
            'nom'              => 'Annexe Cocody',
            'ville'            => 'Cocody',
            'proprietaire_id'  => $admin->id,
            'type'             => 'salon_coiffure',
        ]);

        return [$admin, $principal, $secondaire];
    }

    private function vente(Institut $institut, User $user, int $total, int $joursAvant = 0): Vente
    {
        $vente = Vente::create([
            'institut_id' => $institut->id,
            'user_id'     => $user->id,
            'numero'      => 'V-' . uniqid(),
            'total'       => $total,
            'statut'      => 'validee',
            'mode_paiement' => 'cash',
        ]);

        if ($joursAvant > 0) {
            $vente->forceFill(['created_at' => now()->subDays($joursAvant)])->save();
        }

        return $vente;
    }

    public function test_le_proprietaire_peut_ouvrir_la_fiche(): void
    {
        [$admin, $principal] = $this->contexte();

        $this->actingAs($admin)
            ->get(route('dashboard.mes-instituts.show', $principal))
            ->assertOk()
            ->assertSee($principal->nom)
            ->assertSee('Salon de coiffure');
    }

    public function test_un_etranger_nait_pas_acces_a_la_fiche(): void
    {
        [$admin, $principal] = $this->contexte();
        $intrus = $this->creerAdmin(['email' => 'intrus-' . uniqid() . '@test.com']);

        $this->actingAs($intrus)
            ->get(route('dashboard.mes-instituts.show', $principal))
            ->assertForbidden();
    }

    public function test_la_fiche_affiche_les_chiffres_de_letablissement_demande(): void
    {
        [$admin, $principal, $secondaire] = $this->contexte();

        // L'utilisateur travaille sur l'établissement principal (établissement courant).
        session(['current_institut_id' => $principal->id]);

        // Une vente sur chacun des deux établissements, des montants distincts
        // pour qu'on sache lequel est réellement compté.
        $this->vente($principal, $admin, 25000);
        $this->vente($secondaire, $admin, 90000);

        $html = $this->actingAs($admin)
            ->get(route('dashboard.mes-instituts.show', $secondaire))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('90 000', $html, 'Le CA de l\'établissement demandé doit apparaître.');
        $this->assertStringNotContainsString('25 000', $html, 'Le CA de l\'établissement courant ne doit pas fuiter.');
    }

    public function test_la_fiche_ne_compte_que_les_clients_de_letablissement(): void
    {
        [$admin, $principal, $secondaire] = $this->contexte();

        Client::create([
            'institut_id' => $principal->id,
            'nom'         => 'ClientePrincipale',
            'prenom'      => 'Awa',
            'telephone'   => '0101010101',
            'actif'       => true,
        ]);

        Client::create([
            'institut_id' => $secondaire->id,
            'nom'         => 'ClienteAnnexe',
            'prenom'      => 'Bintou',
            'telephone'   => '0202020202',
            'actif'       => true,
        ]);

        // Deux clients au total, mais un seul sur l'établissement demandé.
        $this->actingAs($admin)
            ->get(route('dashboard.mes-instituts.show', $secondaire))
            ->assertOk()
            ->assertSee('Clients actifs')
            ->assertSee('1');
    }

    public function test_la_fiche_propose_la_modification_et_la_page_publique(): void
    {
        [$admin, $principal] = $this->contexte();
        $principal->forceFill(['vitrine_active' => true, 'slug' => 'institut-test'])->save();

        $this->actingAs($admin)
            ->get(route('dashboard.mes-instituts.show', $principal))
            ->assertOk()
            ->assertSee(route('dashboard.mes-instituts.edit', $principal), false)
            ->assertSee('Modifier')
            ->assertSee('Page publique')
            ->assertSee(route('vitrine.show', $principal->slug), false);
    }

    public function test_la_liste_propose_un_acces_vers_la_fiche(): void
    {
        [$admin, $principal] = $this->contexte();

        $this->actingAs($admin)
            ->get(route('dashboard.mes-instituts.index'))
            ->assertOk()
            ->assertSee(route('dashboard.mes-instituts.show', $principal), false);
    }

    public function test_la_route_statique_apparence_reste_prioritaire(): void
    {
        [$admin] = $this->contexte();

        // Si `mes-instituts/{institut}` était déclarée avant, « apparence »
        // serait interprétée comme un identifiant d'établissement → 404.
        $this->actingAs($admin)
            ->get(route('dashboard.mes-instituts.apparence'))
            ->assertOk();
    }
}
