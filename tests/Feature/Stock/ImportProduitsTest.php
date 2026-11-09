<?php

namespace Tests\Feature\Stock;

use App\Models\EtablissementType;
use App\Models\Institut;
use App\Models\Produit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Import CSV des produits.
 */
class ImportProduitsTest extends TestCase
{
    use RefreshDatabase;

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

    private function csv(string $contenu): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('produits.csv', $contenu);
    }

    public function test_le_formulaire_et_le_modele_sont_accessibles(): void
    {
        [$admin] = $this->contexte();

        $this->actingAs($admin)->get(route('dashboard.produits.import'))
            ->assertOk()
            ->assertSee('Importer des produits');

        $this->actingAs($admin)->get(route('dashboard.produits.import.modele'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_l_import_cree_les_produits(): void
    {
        [$admin, $institut] = $this->contexte();

        $contenu = "nom;reference;prix_achat;prix_vente;stock;seuil_alerte;unite\n"
            . "Huile de ricin;HUIL-001;2500;4500;10;3;flacon\n"
            . "Masque hydratant;MASK-002;1500;3000;5;2;pot\n";

        $this->actingAs($admin)
            ->post(route('dashboard.produits.import.store'), ['fichier' => $this->csv($contenu)])
            ->assertRedirect(route('dashboard.produits.index'));

        $this->assertSame(2, Produit::withoutGlobalScopes()->where('institut_id', $institut->id)->count());

        $huile = Produit::withoutGlobalScopes()->where('reference', 'HUIL-001')->firstOrFail();
        $this->assertSame(4500, (int) $huile->prix_vente);
        $this->assertSame(10, (int) $huile->stock);
    }

    public function test_l_import_met_a_jour_sans_ecraser_le_stock_existant(): void
    {
        [$admin, $institut] = $this->contexte();

        $existant = Produit::withoutGlobalScopes()->create([
            'institut_id' => $institut->id, 'nom' => 'Ancienne huile',
            'reference' => 'HUIL-001', 'prix_achat' => 1000, 'prix_vente' => 2000,
            'stock' => 42, 'seuil_alerte' => 3, 'unite' => 'flacon', 'actif' => true,
        ]);

        $contenu = "nom;reference;prix_achat;prix_vente;stock;seuil_alerte;unite\n"
            . "Huile de ricin;HUIL-001;2500;4500;1;3;flacon\n";

        $this->actingAs($admin)
            ->post(route('dashboard.produits.import.store'), ['fichier' => $this->csv($contenu)]);

        $existant->refresh();

        $this->assertSame('Huile de ricin', $existant->nom);
        $this->assertSame(4500, (int) $existant->prix_vente);
        // Le stock n'est pas écrasé par l'import
        $this->assertSame(42, (int) $existant->stock);
        $this->assertSame(1, Produit::withoutGlobalScopes()->count());
    }

    public function test_le_separateur_virgule_est_accepte(): void
    {
        [$admin, $institut] = $this->contexte();

        $contenu = "nom,reference,prix_achat,prix_vente,stock,seuil_alerte,unite\n"
            . "Sérum,MASK-9,500,1200,4,1,flacon\n";

        $this->actingAs($admin)
            ->post(route('dashboard.produits.import.store'), ['fichier' => $this->csv($contenu)]);

        $this->assertSame(1, Produit::withoutGlobalScopes()->where('institut_id', $institut->id)->count());
    }

    public function test_une_colonne_obligatoire_manquante_est_signalee(): void
    {
        [$admin] = $this->contexte();

        $contenu = "reference;prix_vente\nHUIL-001;4500\n";

        $this->actingAs($admin)
            ->post(route('dashboard.produits.import.store'), ['fichier' => $this->csv($contenu)])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(0, Produit::withoutGlobalScopes()->count());
    }

    public function test_les_lignes_invalides_sont_ignorees_sans_bloquer_l_import(): void
    {
        [$admin, $institut] = $this->contexte();

        $contenu = "nom;reference;prix_achat;prix_vente;stock;seuil_alerte;unite\n"
            . "Produit valide;OK-1;100;500;2;1;u\n"
            . ";VIDE-2;100;500;2;1;u\n"
            . "Prix nul;ZERO-3;100;0;2;1;u\n";

        $this->actingAs($admin)
            ->post(route('dashboard.produits.import.store'), ['fichier' => $this->csv($contenu)])
            ->assertRedirect();

        $this->assertSame(1, Produit::withoutGlobalScopes()->where('institut_id', $institut->id)->count());
        $this->assertSame(2, count(session('import_erreurs') ?? []));
    }
}
