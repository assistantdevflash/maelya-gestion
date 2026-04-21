<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = $this->creerAdmin();

        $response = $this->actingAs($user)
            ->get(route('dashboard.profil.edit'));

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = $this->creerAdmin();

        $response = $this->actingAs($user)
            ->put(route('dashboard.profil.update'), [
                'prenom'      => 'Jean',
                'nom_famille' => 'Dupont',
                'email'       => $user->email,
                'telephone'   => null,
            ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect();

        $user->refresh();
        $this->assertSame('Jean', $user->prenom);
        $this->assertSame('Dupont', $user->nom_famille);
    }

    public function test_email_unique_constraint_on_profile_update(): void
    {
        $user  = $this->creerAdmin();
        $other = $this->creerAdmin();

        $response = $this->actingAs($user)
            ->put(route('dashboard.profil.update'), [
                'prenom'      => 'Jean',
                'nom_famille' => 'Dupont',
                'email'       => $other->email, // email déjà utilisé
                'telephone'   => null,
            ]);

        $response->assertSessionHasErrors('email');
    }
}
