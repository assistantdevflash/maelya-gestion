<?php

namespace Tests\Feature\Admin;

use App\Models\Institut;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Miniature du logo dans la console super admin.
 *
 * `Institut::logo_url` retombe sur le logo de la plateforme quand
 * l'établissement n'en a pas : s'en servir pour décider d'afficher une
 * miniature ferait apparaître le logo Maëlya partout. D'où `aUnLogo()`.
 */
class AdminInstitutLogoTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    private Institut $institut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create(['role' => 'super_admin', 'actif' => true]);

        $admin = $this->creerAdmin();
        $this->institut = Institut::find($admin->institut_id);
        $this->institut->update(['nom' => 'Uriel Project Service Global']);
    }

    private function donnerUnLogo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('logos/uriel.png', 'contenu-image');

        $this->institut->update(['logo' => 'logos/uriel.png']);
    }

    public function test_la_liste_affiche_la_miniature_du_logo(): void
    {
        $this->donnerUnLogo();

        $html = $this->actingAs($this->superAdmin)
            ->get(route('admin.instituts.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('logos/uriel.png', $html);
        $this->assertStringContainsString('Logo de Uriel Project Service Global', $html);
    }

    public function test_la_liste_naffiche_pas_de_logo_quand_letablissement_nen_a_pas(): void
    {
        $html = $this->actingAs($this->superAdmin)
            ->get(route('admin.instituts.index'))
            ->assertOk()
            ->getContent();

        // Repli sur les initiales, jamais une image cassée ni le logo de la
        // plateforme (celui de la barre latérale est un autre sujet).
        $this->assertStringContainsString('Aucun logo', $html);
        $this->assertStringContainsString('UR', $html);
        $this->assertStringNotContainsString('<img src="/storage/', $html);
    }

    public function test_la_fiche_affiche_le_logo_de_letablissement(): void
    {
        $this->donnerUnLogo();

        $html = $this->actingAs($this->superAdmin)
            ->get(route('admin.instituts.show', $this->institut))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('logos/uriel.png', $html);
        $this->assertStringContainsString('Logo de Uriel Project Service Global', $html);
    }

    public function test_la_fiche_signale_labsence_de_logo(): void
    {
        $html = $this->actingAs($this->superAdmin)
            ->get(route('admin.instituts.show', $this->institut))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString("Cet établissement n'a pas encore de logo", $html);
        $this->assertStringNotContainsString('<img src="/storage/', $html);
    }

    public function test_un_logo_supprime_du_disque_nest_pas_affiche(): void
    {
        // Logo resté en base mais fichier disparu : on ne doit pas produire
        // d'image cassée ni afficher le logo de la plateforme.
        $this->institut->update(['logo' => 'logos/disparu.png']);

        $this->assertFalse($this->institut->fresh()->aUnLogo());

        $this->actingAs($this->superAdmin)
            ->get(route('admin.instituts.show', $this->institut))
            ->assertOk()
            ->assertDontSee('logos/disparu.png');
    }
}
