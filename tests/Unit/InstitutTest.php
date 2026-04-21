<?php

namespace Tests\Unit;

use App\Models\Institut;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstitutTest extends TestCase
{
    use RefreshDatabase;

    public function test_slug_auto_genere_a_la_creation(): void
    {
        $institut = Institut::create([
            'nom'       => 'Salon Beauté',
            'email'     => 'salon@test.com',
            'telephone' => '0102030405',
            'ville'     => 'Abidjan',
            'type'      => 'salon_coiffure',
            'actif'     => true,
        ]);

        $this->assertNotNull($institut->slug);
        $this->assertStringStartsWith('salon-beaute', $institut->slug);
    }

    public function test_slug_custom_est_preserve(): void
    {
        $institut = Institut::create([
            'nom'       => 'Mon Institut',
            'slug'      => 'mon-slug-custom',
            'email'     => 'custom@test.com',
            'telephone' => '0102030405',
            'ville'     => 'Abidjan',
            'type'      => 'spa',
            'actif'     => true,
        ]);

        $this->assertSame('mon-slug-custom', $institut->slug);
    }

    public function test_actif_cast_en_boolean(): void
    {
        $institut = $this->creerInstitut(['actif' => true]);

        $this->assertIsBool($institut->actif);
        $this->assertTrue($institut->actif);
    }

    public function test_relation_users(): void
    {
        $user    = $this->creerAdmin();
        $institut = $user->institut;

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $institut->users);
        $this->assertTrue($institut->users->contains($user));
    }

    public function test_relation_clients(): void
    {
        $user     = $this->creerAdmin();
        $this->actingAs($user);
        $institut = $user->institut;

        \App\Models\Client::create([
            'prenom'    => 'Test',
            'nom'       => 'Client',
            'telephone' => '0000',
            'actif'     => true,
        ]);

        $this->assertCount(1, $institut->clients);
    }
}
