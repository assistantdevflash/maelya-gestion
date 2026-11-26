<?php

namespace Tests\Feature\Commercial;

use App\Models\Abonnement;
use App\Models\CommercialCommission;
use App\Models\CommercialParrainage;
use App\Models\CommercialProfile;
use App\Models\Institut;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Attribution d'un établissement (compte client) à un commercial depuis la
 * console super admin.
 *
 * Le parrainage commercial porte sur le compte, pas sur l'établissement :
 * les commissions sont calculées sur les abonnements du propriétaire. On
 * enregistre donc l'établissement d'entrée pour que le commercial retrouve
 * celui qu'on lui a attribué.
 */
class AttributionEstablishmentTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private User $proprietaire;

    private Institut $institut;

    private CommercialProfile $profil;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('commercial_config')->insertOrIgnore(['taux' => 20, 'duree_mois' => 6]);

        $this->superAdmin = User::factory()->create(['role' => 'super_admin', 'actif' => true]);

        $this->proprietaire = $this->creerAdmin();
        $this->institut = Institut::find($this->proprietaire->institut_id);

        $commercialUser = User::factory()->create([
            'role'        => 'commercial',
            'actif'       => true,
            'prenom'      => 'Hamade',
            'nom_famille' => 'Attalah',
        ]);

        $this->profil = CommercialProfile::create([
            'user_id' => $commercialUser->id,
            'code'    => 'HAAT5170',
        ]);
    }

    private function attribuer(?string $commercialId = null)
    {
        return $this->actingAs($this->superAdmin)
            ->patch(route('admin.instituts.commercial', $this->institut), [
                'commercial_id' => $commercialId ?? $this->profil->id,
            ]);
    }

    // ── Attribution ───────────────────────────────────────────────────────────

    public function test_un_super_admin_peut_attribuer_un_etablissement_a_un_commercial(): void
    {
        $this->attribuer()->assertRedirect();

        $parrainage = CommercialParrainage::where('proprietaire_id', $this->proprietaire->id)->first();

        $this->assertNotNull($parrainage, 'Le parrainage doit être créé.');
        $this->assertSame($this->profil->id, $parrainage->commercial_id);
        $this->assertSame($this->institut->id, $parrainage->institut_id);
        $this->assertSame('manuel', $parrainage->source);
        $this->assertSame($this->superAdmin->id, $parrainage->attribue_par);
        $this->assertTrue($parrainage->isActif(), 'La période de commission doit être ouverte.');
    }

    public function test_la_duree_de_commission_suit_la_configuration(): void
    {
        DB::table('commercial_config')->update(['duree_mois' => 3]);

        $this->attribuer();

        $parrainage = CommercialParrainage::where('proprietaire_id', $this->proprietaire->id)->first();

        $this->assertSame(now()->addMonths(3)->toDateString(), $parrainage->expire_le->toDateString());
    }

    public function test_reattribuer_le_meme_commercial_ne_repousse_pas_lecheance(): void
    {
        $this->attribuer();

        $avant = CommercialParrainage::where('proprietaire_id', $this->proprietaire->id)->first();
        $echeance = $avant->expire_le->toDateString();

        $this->travel(2)->months();
        $this->attribuer();

        $apres = CommercialParrainage::where('proprietaire_id', $this->proprietaire->id)->first();

        $this->assertSame($echeance, $apres->expire_le->toDateString());
        $this->assertSame(1, CommercialParrainage::where('proprietaire_id', $this->proprietaire->id)->count());
    }

    public function test_attribuer_un_autre_commercial_remplace_le_parrainage(): void
    {
        $this->attribuer();

        $autreUser = User::factory()->create(['role' => 'commercial', 'actif' => true]);
        $autre = CommercialProfile::create(['user_id' => $autreUser->id, 'code' => 'AUTRE01']);

        $this->attribuer($autre->id);

        $this->assertSame(1, CommercialParrainage::where('proprietaire_id', $this->proprietaire->id)->count());
        $this->assertSame(
            $autre->id,
            CommercialParrainage::where('proprietaire_id', $this->proprietaire->id)->value('commercial_id')
        );
    }

    public function test_on_peut_retirer_le_commercial(): void
    {
        $this->attribuer();

        $this->actingAs($this->superAdmin)
            ->patch(route('admin.instituts.commercial', $this->institut), ['commercial_id' => ''])
            ->assertRedirect();

        $this->assertSame(0, CommercialParrainage::where('proprietaire_id', $this->proprietaire->id)->count());
    }

    public function test_un_commercial_desactive_ne_peut_pas_recevoir_un_etablissement(): void
    {
        $this->profil->user->update(['actif' => false]);

        $this->actingAs($this->superAdmin)
            ->patch(route('admin.instituts.commercial', $this->institut), [
                'commercial_id' => $this->profil->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(0, CommercialParrainage::where('proprietaire_id', $this->proprietaire->id)->count());
    }

    public function test_un_non_super_admin_ne_peut_pas_attribuer(): void
    {
        $this->actingAs($this->proprietaire)
            ->patch(route('admin.instituts.commercial', $this->institut), [
                'commercial_id' => $this->profil->id,
            ])
            ->assertForbidden();

        $this->assertSame(0, CommercialParrainage::count());
    }

    // ── Bénéfices identiques à une inscription par code ───────────────────────

    public function test_une_commission_est_generee_pour_un_etablissement_attribue(): void
    {
        $this->attribuer();

        $plan = $this->creerPlan(['slug' => 'mensuel-' . uniqid(), 'prix' => 10000, 'duree_jours' => 30]);

        $abonnement = Abonnement::create([
            'user_id'             => $this->proprietaire->id,
            'plan_id'             => $plan->id,
            'montant'             => 10000,
            'periode'             => 'mensuel',
            'statut'              => 'en_attente',
            'reference_transfert' => 'REF-' . uniqid(),
            'debut_le'            => null,
            'expire_le'           => null,
        ]);

        $this->actingAs($this->superAdmin)
            ->patch(route('admin.abonnements.valider', $abonnement))
            ->assertRedirect();

        $commission = CommercialCommission::where('abonnement_id', $abonnement->id)->first();

        $this->assertNotNull($commission, 'Un abonnement validé doit générer une commission.');
        $this->assertSame($this->profil->id, $commission->commercial_id);
        $this->assertSame(2000, (int) $commission->montant); // 10000 × 20 %
    }

    // ── Visibilité ────────────────────────────────────────────────────────────

    public function test_la_liste_admin_indique_le_commercial_du_parrainage(): void
    {
        $this->attribuer();

        $this->actingAs($this->superAdmin)
            ->get(route('admin.instituts.index'))
            ->assertOk()
            ->assertSee('Parrainé par Hamade Attalah')
            ->assertSee('HAAT5170');
    }

    public function test_la_liste_admin_indique_les_comptes_sans_commercial(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('admin.instituts.index'))
            ->assertOk()
            ->assertSee('Sans commercial');
    }

    public function test_le_filtre_parrainage_isole_les_comptes_attribues(): void
    {
        $this->attribuer();

        $sansLien = $this->creerAdmin(
            ['email' => 'libre-' . uniqid() . '@test.com'],
            ['nom' => 'Etablissement Libre']
        );

        $this->actingAs($this->superAdmin)
            ->get(route('admin.instituts.index', ['parrainage' => 'avec']))
            ->assertOk()
            ->assertSee($this->institut->nom)
            ->assertDontSee('Etablissement Libre');

        $this->actingAs($this->superAdmin)
            ->get(route('admin.instituts.index', ['parrainage' => 'sans']))
            ->assertOk()
            ->assertSee('Etablissement Libre')
            ->assertDontSee($this->institut->nom);
    }

    public function test_le_commercial_voit_letablissement_attribue(): void
    {
        $this->attribuer();

        $this->actingAs($this->profil->user)
            ->get(route('commercial.parrainages'))
            ->assertOk()
            ->assertSee($this->institut->nom)
            ->assertSee($this->proprietaire->nom_complet);
    }
}
