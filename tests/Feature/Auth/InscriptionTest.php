<?php

namespace Tests\Feature\Auth;

use App\Models\Institut;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use App\Mail\BienvenueMaelya;
use Tests\TestCase;

class InscriptionTest extends TestCase
{
    use RefreshDatabase;

    private array $donneesValides = [
        'nom_institut'      => 'Salon Beauté Awa',
        'type_institut'     => 'salon_coiffure',
        'ville'             => 'Abidjan',
        'telephone_institut'=> '0102030405',
        'prenom'            => 'Awa',
        'nom_famille'       => 'Koné',
        'email'             => 'awa@example.com',
        'telephone'         => '0707070707',
        'password'          => 'Password123!',
        'password_confirmation' => 'Password123!',
        'cgu'               => '1',
    ];

    public function test_page_inscription_est_accessible(): void
    {
        $this->get(route('inscription'))
            ->assertOk();
    }

    public function test_inscription_cree_user_et_institut(): void
    {
        Mail::fake();

        $this->post(route('inscription.store'), $this->donneesValides)
            ->assertRedirect(route('dashboard.index'));

        $this->assertDatabaseHas('users', [
            'email'      => 'awa@example.com',
            'prenom'     => 'Awa',
            'nom_famille'=> 'Koné',
            'role'       => 'admin',
        ]);

        $this->assertDatabaseHas('instituts', [
            'nom'  => 'Salon Beauté Awa',
            'ville'=> 'Abidjan',
        ]);
    }

    public function test_inscription_cree_abonnement_essai_14_jours(): void
    {
        Mail::fake();

        $this->creerPlan(['slug' => 'essai', 'duree_jours' => 14]);

        $this->post(route('inscription.store'), $this->donneesValides);

        $user = User::where('email', 'awa@example.com')->first();

        $this->assertNotNull($user);
        $this->assertDatabaseHas('abonnements', [
            'user_id' => $user->id,
            'statut'  => 'actif',
        ]);
    }

    public function test_inscription_connecte_automatiquement(): void
    {
        Mail::fake();

        $this->post(route('inscription.store'), $this->donneesValides);

        $user = User::where('email', 'awa@example.com')->first();
        $this->assertAuthenticatedAs($user);
    }

    public function test_inscription_envoie_email_bienvenue(): void
    {
        Mail::fake();

        $this->post(route('inscription.store'), $this->donneesValides);

        Mail::assertSent(BienvenueMaelya::class, function ($mail) {
            return $mail->hasTo('awa@example.com');
        });
    }

    public function test_inscription_echoue_si_email_deja_pris(): void
    {
        Mail::fake();

        // Créer un utilisateur avec cet email d'abord
        $this->creerAdmin(['email' => 'awa@example.com']);

        $this->post(route('inscription.store'), $this->donneesValides)
            ->assertSessionHasErrors(['email']);
    }

    public function test_inscription_echoue_si_champs_requis_manquants(): void
    {
        $this->post(route('inscription.store'), [])
            ->assertSessionHasErrors(['nom_institut', 'type_institut', 'ville', 'prenom', 'nom_famille', 'email', 'password', 'cgu']);
    }

    public function test_inscription_echoue_si_cgu_non_acceptees(): void
    {
        $data = $this->donneesValides;
        unset($data['cgu']);

        $this->post(route('inscription.store'), $data)
            ->assertSessionHasErrors(['cgu']);
    }

    public function test_inscription_echoue_si_mots_de_passe_differents(): void
    {
        $data = array_merge($this->donneesValides, ['password_confirmation' => 'AutreMotDePasse!']);

        $this->post(route('inscription.store'), $data)
            ->assertSessionHasErrors(['password']);
    }
}
