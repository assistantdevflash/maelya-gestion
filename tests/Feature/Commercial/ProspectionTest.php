<?php

namespace Tests\Feature\Commercial;

use App\Models\Abonnement;
use App\Models\CommercialParrainage;
use App\Models\CommercialProfile;
use App\Models\CommercialProspect;
use App\Models\User;
use App\Services\ProspectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Espace commercial : pipeline de prospection et relances.
 */
class ProspectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Les types d'activité viennent de la table de référence de la
        // plateforme : sans ce seed, la validation « type_activite » échoue.
        $this->seedTypesEtablissement();
    }

    private function creerCommercial(): array
    {
        $user = User::factory()->create([
            'prenom'      => 'Awa',
            'nom_famille' => 'Diomandé',
            'role'        => 'commercial',
            'actif'       => true,
            'telephone'   => '0700000000',
        ]);

        $profil = CommercialProfile::create([
            'user_id' => $user->id,
            'code'    => strtoupper(Str::random(6)),
        ]);

        return [$user, $profil];
    }

    private function creerProspect(CommercialProfile $profil, array $attrs = []): CommercialProspect
    {
        return CommercialProspect::create(array_merge([
            'commercial_id'     => $profil->id,
            'nom_etablissement' => 'Salon Élégance',
            'type_activite'     => 'salon_coiffure',
            'ville'             => 'Abidjan',
            'contact_nom'       => 'Mme Koffi',
            'contact_telephone' => '0708091011',
            'statut'            => 'contacte',
            'source'            => 'porte_a_porte',
            'priorite'          => 'normale',
        ], $attrs));
    }

    public function test_le_commercial_accede_a_son_pipeline(): void
    {
        [$user, $profil] = $this->creerCommercial();
        $this->creerProspect($profil);

        $html = $this->actingAs($user)
            ->get(route('commercial.prospection'))
            ->assertOk()
            ->assertSee('Salon Élégance')
            ->assertSee('Prospection')
            ->getContent();

        // Le changement d'étape passe désormais par fetch : plus aucun
        // formulaire ne doit recharger la page au changement de statut.
        $this->assertStringContainsString('ficheProspect(', $html);
        $this->assertStringNotContainsString('this.form.submit()', $html);
        $this->assertStringContainsString('changerStatut(', $html);

        // La zone d'action doit s'expliquer d'elle-même.
        $this->assertStringContainsString('Où en est ce prospect', $html);
        $this->assertStringContainsString('enregistré aussitôt', $html);
        $this->assertStringContainsString('Journal des échanges', $html);
        $this->assertStringContainsString('Prochaine relance', $html);
    }

    public function test_un_commercial_peut_ajouter_un_prospect(): void
    {
        [$user, $profil] = $this->creerCommercial();

        $this->actingAs($user)
            ->post(route('commercial.prospection.store'), [
                'nom_etablissement'    => 'Spa Sérénité',
                'type_activite'        => 'institut_beaute',
                'ville'                => 'Abidjan',
                'contact_telephone'    => '0102030405',
                'priorite'             => 'haute',
                'prochaine_relance_le' => today()->addDays(3)->format('Y-m-d'),
            ])
            ->assertRedirect();

        $prospect = CommercialProspect::where('nom_etablissement', 'Spa Sérénité')->firstOrFail();
        $this->assertSame($profil->id, $prospect->commercial_id);
        $this->assertSame('a_contacter', $prospect->statut);
        $this->assertSame(1, $prospect->activites()->count(), 'La création est journalisée.');
    }

    public function test_un_commercial_ne_peut_pas_toucher_le_prospect_d_un_autre(): void
    {
        [$userA, $profilA] = $this->creerCommercial();
        [, $profilB] = $this->creerCommercial();

        $prospectDeB = $this->creerProspect($profilB);

        $this->actingAs($userA)
            ->patch(route('commercial.prospection.statut', $prospectDeB), ['statut' => 'perdu'])
            ->assertNotFound();

        $this->assertSame('contacte', $prospectDeB->fresh()->statut);
    }

    public function test_un_prospect_converti_sort_de_la_file_de_relance(): void
    {
        [$user, $profil] = $this->creerCommercial();

        $prospect = $this->creerProspect($profil, [
            'prochaine_relance_le' => today()->subDays(2)->format('Y-m-d'),
        ]);

        $this->assertSame(1, $profil->prospects()->aRelancer()->count());

        $this->actingAs($user)
            ->patch(route('commercial.prospection.statut', $prospect), ['statut' => 'converti'])
            ->assertRedirect();

        $prospect->refresh();
        $this->assertSame('converti', $prospect->statut);
        $this->assertNotNull($prospect->converti_le);
        $this->assertNull($prospect->prochaine_relance_le);
        $this->assertSame(0, $profil->prospects()->aRelancer()->count());
    }

    public function test_le_changement_de_statut_repond_en_json_pour_le_front(): void
    {
        [$user, $profil] = $this->creerCommercial();

        $prospect = $this->creerProspect($profil, [
            'prochaine_relance_le' => today()->subDays(2)->format('Y-m-d'),
        ]);

        $reponse = $this->actingAs($user)->patchJson(
            route('commercial.prospection.statut', $prospect),
            ['statut' => 'interesse']
        );

        $reponse->assertOk();
        $reponse->assertJson([
            'ok'      => true,
            'statut'  => 'interesse',
            'libelle' => 'Intéressé',
        ]);

        // La réponse porte le libellé de relance prêt à afficher : le front ne
        // recalcule rien.
        $this->assertSame('Relance en retard (2 j)', $reponse->json('relance'));
        $this->assertSame('retard', $reponse->json('niveau'));
        $this->assertSame('interesse', $prospect->fresh()->statut);
    }

    public function test_une_conversion_en_json_retire_la_relance(): void
    {
        [$user, $profil] = $this->creerCommercial();

        $prospect = $this->creerProspect($profil, [
            'prochaine_relance_le' => today()->addDays(4)->format('Y-m-d'),
        ]);

        $reponse = $this->actingAs($user)->patchJson(
            route('commercial.prospection.statut', $prospect),
            ['statut' => 'converti']
        );

        $reponse->assertOk();
        $this->assertNull($reponse->json('relance'));
        $this->assertNull($reponse->json('niveau'));
    }

    public function test_l_ajout_d_une_interaction_repond_en_json(): void
    {
        [$user, $profil] = $this->creerCommercial();
        $prospect = $this->creerProspect($profil);

        $reponse = $this->actingAs($user)->postJson(
            route('commercial.prospection.activite', $prospect),
            [
                'type'    => 'appel',
                'contenu' => 'Rappelée, elle demande une démo vendredi.',
                'fait_le' => today()->format('Y-m-d'),
            ]
        );

        $reponse->assertOk();
        $this->assertSame(1, $reponse->json('nb_interactions'));
        $this->assertSame('Appel', $reponse->json('activite.type'));
        $this->assertStringContainsString('démo vendredi', $reponse->json('activite.contenu'));
    }

    public function test_la_planification_de_relance_repond_en_json(): void
    {
        [$user, $profil] = $this->creerCommercial();
        $prospect = $this->creerProspect($profil, ['prochaine_relance_le' => null]);

        $reponse = $this->actingAs($user)->patchJson(
            route('commercial.prospection.relance', $prospect),
            ['prochaine_relance_le' => today()->addDays(3)->format('Y-m-d')]
        );

        $reponse->assertOk();
        $this->assertSame('Relance dans 3 j', $reponse->json('relance'));
        $this->assertSame('proche', $reponse->json('niveau'));
    }

    public function test_les_relances_en_retard_sont_identifiees(): void
    {
        [, $profil] = $this->creerCommercial();

        $this->creerProspect($profil, [
            'nom_etablissement'    => 'En retard',
            'prochaine_relance_le' => today()->subDays(4)->format('Y-m-d'),
        ]);
        $this->creerProspect($profil, [
            'nom_etablissement'    => 'Aujourd hui',
            'prochaine_relance_le' => today()->format('Y-m-d'),
        ]);
        $this->creerProspect($profil, [
            'nom_etablissement'    => 'Plus tard',
            'prochaine_relance_le' => today()->addDays(10)->format('Y-m-d'),
        ]);
        $this->creerProspect($profil, [
            'nom_etablissement'    => 'Sans date',
            'prochaine_relance_le' => null,
        ]);

        $service = app(ProspectionService::class);

        $this->assertSame(2, $profil->prospects()->aRelancer()->count());
        $this->assertSame(1, $profil->prospects()->enRetard()->count());
        $this->assertCount(2, $service->relancesDues($profil));
        $this->assertCount(1, $service->prospectsSansRelance($profil));
    }

    public function test_les_abonnements_des_filleuls_arrivent_dans_les_echeances(): void
    {
        [, $profil] = $this->creerCommercial();

        $plan = $this->creerPlan();

        // Filleul dont l'abonnement expire dans 5 jours
        $filleul = $this->creerAdmin();
        $filleul->abonnements()->delete();
        Abonnement::create([
            'user_id'   => $filleul->id,
            'plan_id'   => $plan->id,
            'montant'   => 10000,
            'periode'   => 'mensuel',
            'statut'    => 'actif',
            'debut_le'  => now()->subDays(25),
            'expire_le' => today()->addDays(5),
        ]);

        CommercialParrainage::create([
            'commercial_id'  => $profil->id,
            'proprietaire_id' => $filleul->id,
            'expire_le'      => today()->addMonths(3),
        ]);

        $echeances = app(ProspectionService::class)->echeancesAbonnements($profil);

        $this->assertCount(1, $echeances);
        $this->assertSame(5, $echeances->first()['jours']);
        $this->assertSame('urgent', $echeances->first()['niveau']);
    }

    public function test_un_abonnement_lointain_n_est_pas_une_echeance(): void
    {
        [, $profil] = $this->creerCommercial();

        $plan = $this->creerPlan();

        $filleul = $this->creerAdmin();
        $filleul->abonnements()->delete();
        Abonnement::create([
            'user_id'   => $filleul->id,
            'plan_id'   => $plan->id,
            'montant'   => 10000,
            'periode'   => 'annuel',
            'statut'    => 'actif',
            'debut_le'  => now(),
            'expire_le' => today()->addMonths(11),
        ]);

        CommercialParrainage::create([
            'commercial_id'  => $profil->id,
            'proprietaire_id' => $filleul->id,
            'expire_le'      => today()->addMonths(3),
        ]);

        $this->assertCount(0, app(ProspectionService::class)->echeancesAbonnements($profil));
    }

    public function test_le_tableau_de_bord_montre_le_travail_du_jour(): void
    {
        [$user, $profil] = $this->creerCommercial();

        $this->creerProspect($profil, [
            'prochaine_relance_le' => today()->subDay()->format('Y-m-d'),
        ]);

        $this->actingAs($user)
            ->get(route('commercial.dashboard'))
            ->assertOk()
            ->assertSee("À faire aujourd'hui", false)
            ->assertSee('Salon Élégance')
            ->assertSee('Prospects en cours');
    }

    public function test_la_page_relances_affiche_les_echeances(): void
    {
        [$user, $profil] = $this->creerCommercial();

        $this->creerProspect($profil, [
            'prochaine_relance_le' => today()->subDay()->format('Y-m-d'),
        ]);

        $this->actingAs($user)
            ->get(route('commercial.relances'))
            ->assertOk()
            ->assertSee('Relances')
            ->assertSee('Salon Élégance');
    }

    public function test_planifier_une_relance_enregistre_la_date_et_l_historique(): void
    {
        [$user, $profil] = $this->creerCommercial();
        $prospect = $this->creerProspect($profil, ['prochaine_relance_le' => null]);

        $this->actingAs($user)
            ->patch(route('commercial.prospection.relance', $prospect), [
                'prochaine_relance_le' => today()->addDays(5)->format('Y-m-d'),
            ])
            ->assertRedirect();

        $prospect->refresh();
        $this->assertSame(today()->addDays(5)->format('Y-m-d'), $prospect->prochaine_relance_le->format('Y-m-d'));
        $this->assertSame(1, $prospect->activites()->where('type', 'relance')->count());
    }

    public function test_ajouter_une_interaction_met_a_jour_la_derniere_date_de_contact(): void
    {
        [$user, $profil] = $this->creerCommercial();
        $prospect = $this->creerProspect($profil);

        $this->actingAs($user)
            ->post(route('commercial.prospection.activite', $prospect), [
                'type'    => 'visite',
                'contenu' => 'Passé au salon, la gérante veut une démo la semaine prochaine.',
                'fait_le' => today()->format('Y-m-d'),
            ])
            ->assertRedirect();

        $prospect->refresh();
        $this->assertSame(today()->format('Y-m-d'), $prospect->derniere_interaction_le->format('Y-m-d'));
        $this->assertSame(1, $prospect->activites()->count());
    }

    public function test_la_page_parrainages_distingue_commission_et_abonnement(): void
    {
        [$user, $profil] = $this->creerCommercial();

        $filleul = $this->creerAdmin();
        $filleul->abonnements()->delete();

        // L'abonnement du filleul court encore 8 jours…
        Abonnement::create([
            'user_id'   => $filleul->id,
            'plan_id'   => $this->creerPlan()->id,
            'montant'   => 10000,
            'periode'   => 'mensuel',
            'statut'    => 'actif',
            'debut_le'  => now()->subDays(22),
            'expire_le' => today()->addDays(8),
        ]);

        // …alors que la période de commission du commercial est terminée.
        CommercialParrainage::create([
            'commercial_id'   => $profil->id,
            'proprietaire_id' => $filleul->id,
            'expire_le'       => today()->subDays(15),
        ]);

        $html = $this->actingAs($user)
            ->get(route('commercial.parrainages'))
            ->assertOk()
            ->getContent();

        // L'échéance réelle de l'abonnement est visible…
        $this->assertStringContainsString('dans 8 j', $html);

        // …et la commission est présentée comme terminée, pas l'abonnement
        // comme « expiré » (confusion qui faisait croire à un client perdu).
        $this->assertStringContainsString('Terminée', $html);
        $this->assertStringNotContainsString('>Expiré<', $html);
    }

    public function test_le_layout_commercial_expose_le_jeton_csrf(): void
    {
        [$user] = $this->creerCommercial();

        $html = $this->actingAs($user)
            ->get(route('commercial.prospection'))
            ->assertOk()
            ->getContent();

        // Sans cette balise, toutes les actions fetch (changement d'étape,
        // interaction, relance) partaient avec un jeton vide → 419.
        $this->assertStringContainsString('name="csrf-token"', $html);
    }

    public function test_un_administrateur_n_accede_pas_a_l_espace_commercial(): void
    {
        $admin = $this->creerAdmin();

        $this->actingAs($admin)
            ->get(route('commercial.prospection'))
            ->assertForbidden();
    }
}
