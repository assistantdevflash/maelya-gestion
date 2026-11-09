<?php

namespace Tests\Feature\Stock;

use App\Models\EtablissementType;
use App\Models\Institut;
use App\Models\Produit;
use App\Models\ProduitImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Publication en boutique conditionnée à la présence d'une image.
 *
 * Un produit sans visuel s'affichait avec un pictogramme gris dans la vitrine :
 * rien n'empêchait de le publier, alors que cela n'apporte rien au client.
 */
class ProduitImageBoutiqueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function contexte(): array
    {
        EtablissementType::create([
            'code' => 'salon_coiffure', 'libelle' => 'Salon',
            'actif' => true, 'position' => 1,
        ]);

        $admin = $this->creerAdmin(['role' => 'admin']);
        $institut = Institut::find($admin->institut_id);

        return [$admin, $institut];
    }

    private function donnees(array $surcharge = []): array
    {
        return array_merge([
            'nom'        => 'Huile de ricin',
            'prix_vente' => 4500,
            'prix_achat' => 2500,
            'stock'      => 10,
            'seuil_alerte' => 3,
            'unite'      => 'flacon',
        ], $surcharge);
    }

    private function produit(Institut $institut, array $attrs = []): Produit
    {
        return Produit::withoutGlobalScopes()->create(array_merge([
            'institut_id' => $institut->id, 'nom' => 'Masque',
            'prix_achat' => 1000, 'prix_vente' => 2000, 'stock' => 5,
            'seuil_alerte' => 2, 'unite' => 'pot', 'actif' => true,
            'visible_boutique' => false,
        ], $attrs));
    }

    public function test_a_une_image_reconnait_la_colonne_photo(): void
    {
        [$admin, $institut] = $this->contexte();

        $this->assertFalse($this->produit($institut)->aUneImage());
        $this->assertTrue($this->produit($institut, ['photo' => 'produits/x.jpg'])->aUneImage());
    }

    public function test_a_une_image_reconnait_la_galerie(): void
    {
        [$admin, $institut] = $this->contexte();

        $produit = $this->produit($institut);
        ProduitImage::create([
            'produit_id' => $produit->id,
            'chemin'     => 'produits/galerie.jpg',
            'principale' => true,
            'ordre'      => 0,
        ]);

        $this->assertTrue($produit->fresh()->aUneImage());
    }

    public function test_creation_avec_photo_est_publiee(): void
    {
        [$admin] = $this->contexte();

        $this->actingAs($admin)->post(route('dashboard.produits.store'), $this->donnees([
            'visible_boutique' => '1',
            'photo'            => UploadedFile::fake()->image('huile.jpg', 600, 600),
        ]))->assertRedirect(route('dashboard.produits.index'));

        $this->assertTrue((bool) Produit::withoutGlobalScopes()->firstOrFail()->visible_boutique);
    }

    public function test_creation_sans_photo_ne_publie_pas_et_avertit(): void
    {
        [$admin] = $this->contexte();

        $this->actingAs($admin)
            ->post(route('dashboard.produits.store'), $this->donnees(['visible_boutique' => '1']))
            ->assertRedirect(route('dashboard.produits.index'))
            ->assertSessionHas('warning');

        $this->assertFalse((bool) Produit::withoutGlobalScopes()->firstOrFail()->visible_boutique);
    }

    public function test_le_toggle_refuse_un_produit_sans_image(): void
    {
        [$admin, $institut] = $this->contexte();
        $produit = $this->produit($institut);

        $this->actingAs($admin)
            ->post(route('dashboard.produits.toggle-visible', $produit))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertFalse((bool) $produit->fresh()->visible_boutique);
    }

    public function test_le_toggle_accepte_un_produit_illustre(): void
    {
        [$admin, $institut] = $this->contexte();
        $produit = $this->produit($institut, ['photo' => 'produits/ok.jpg']);

        $this->actingAs($admin)
            ->post(route('dashboard.produits.toggle-visible', $produit))
            ->assertSessionHas('success');

        $this->assertTrue((bool) $produit->fresh()->visible_boutique);
    }

    public function test_l_action_groupee_ignore_les_produits_sans_image(): void
    {
        [$admin, $institut] = $this->contexte();

        $avecImage   = $this->produit($institut, ['nom' => 'Avec', 'photo' => 'produits/a.jpg']);
        $sansImage   = $this->produit($institut, ['nom' => 'Sans']);
        $avecGalerie = $this->produit($institut, ['nom' => 'Galerie']);
        ProduitImage::create([
            'produit_id' => $avecGalerie->id, 'chemin' => 'produits/g.jpg',
            'principale' => true, 'ordre' => 0,
        ]);

        $this->actingAs($admin)
            ->post(route('dashboard.produits.actions-groupees'), [
                'ids'    => [$avecImage->id, $sansImage->id, $avecGalerie->id],
                'action' => 'boutique_on',
            ])
            ->assertSessionHas('warning');

        $this->assertTrue((bool) $avecImage->fresh()->visible_boutique);
        $this->assertTrue((bool) $avecGalerie->fresh()->visible_boutique);
        $this->assertFalse((bool) $sansImage->fresh()->visible_boutique);
    }
}
