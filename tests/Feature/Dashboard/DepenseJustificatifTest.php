<?php

namespace Tests\Feature\Dashboard;

use App\Models\Depense;
use App\Services\FinanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Justificatif de dépense : référence écrite et pièce jointe.
 *
 * La colonne `justificatif` existait mais aucun formulaire ne l'alimentait :
 * le compteur « sans justificatif » valait donc toujours le nombre total de
 * dépenses, et l'avertissement ne pouvait jamais être levé.
 */
class DepenseJustificatifTest extends TestCase
{
    use RefreshDatabase;

    private $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->admin = $this->creerAdmin();
    }

    private function depot(array $donnees = [])
    {
        return $this->actingAs($this->admin)->post(route('dashboard.depenses.store'), array_merge([
            'description' => 'Achat de style',
            'categorie'   => 'equipement',
            'montant'     => 15000,
            'date'        => now()->toDateString(),
        ], $donnees));
    }

    private function sansJustificatif(): int
    {
        return app(FinanceService::class)->depensesSansJustificatif(
            $this->admin->institut_id,
            now()->startOfMonth(),
            now()->endOfMonth()
        );
    }

    public function test_une_depense_sans_reference_est_comptee_sans_justificatif(): void
    {
        $this->depot()->assertRedirect();

        $this->assertSame(1, $this->sansJustificatif());
        $this->assertFalse(Depense::firstOrFail()->aUnJustificatif());
    }

    public function test_une_reference_ecrite_suffit_a_justifier(): void
    {
        $this->depot(['justificatif' => 'Reçu n° 412 – Boutique Adjamé'])->assertRedirect();

        $depense = Depense::firstOrFail();

        $this->assertSame('Reçu n° 412 – Boutique Adjamé', $depense->justificatif);
        $this->assertTrue($depense->aUnJustificatif());
        $this->assertFalse($depense->aUnePieceJointe());
        $this->assertSame(0, $this->sansJustificatif());
    }

    public function test_une_photo_de_recu_est_stockee_et_compte_comme_justificatif(): void
    {
        $this->depot([
            'justificatif_fichier' => UploadedFile::fake()->image('recu.jpg'),
        ])->assertRedirect();

        $depense = Depense::firstOrFail();

        $this->assertTrue($depense->aUnePieceJointe());
        $this->assertNotNull($depense->justificatif_fichier);
        Storage::disk('public')->assertExists($depense->justificatif_fichier);
        $this->assertStringStartsWith('justificatifs/', $depense->justificatif_fichier);
        $this->assertStringContainsString('/storage/', (string) $depense->justificatif_url);
        $this->assertSame(0, $this->sansJustificatif());
    }

    public function test_un_pdf_de_recu_est_accepte(): void
    {
        $this->depot([
            'justificatif_fichier' => UploadedFile::fake()->create('facture.pdf', 120, 'application/pdf'),
        ])->assertRedirect();

        $this->assertTrue(Depense::firstOrFail()->aUnePieceJointe());
    }

    public function test_un_fichier_non_supporte_est_refuse(): void
    {
        $this->actingAs($this->admin)->post(route('dashboard.depenses.store'), [
            'description'          => 'Suspect',
            'categorie'            => 'autres',
            'montant'              => 1000,
            'date'                 => now()->toDateString(),
            'justificatif_fichier' => UploadedFile::fake()->create('script.exe', 10),
        ])->assertSessionHasErrors('justificatif_fichier');

        $this->assertSame(0, Depense::count());
    }

    public function test_modifier_la_reference_conserve_la_piece_jointe(): void
    {
        $this->depot([
            'justificatif_fichier' => UploadedFile::fake()->image('recu.jpg'),
        ]);

        $depense = Depense::firstOrFail();
        $chemin  = $depense->justificatif_fichier;

        $this->actingAs($this->admin)->put(route('dashboard.depenses.update', $depense), [
            'description'  => 'Achat de style (corrigé)',
            'categorie'    => 'equipement',
            'montant'      => 16000,
            'date'         => now()->toDateString(),
            'justificatif' => 'Reçu n° 412',
        ])->assertRedirect();

        $depense->refresh();

        $this->assertSame('Reçu n° 412', $depense->justificatif);
        $this->assertSame($chemin, $depense->justificatif_fichier, 'La pièce jointe ne doit pas être effacée.');
        Storage::disk('public')->assertExists($chemin);
    }

    public function test_remplacer_la_piece_jointe_supprime_l_ancienne(): void
    {
        $this->depot(['justificatif_fichier' => UploadedFile::fake()->image('ancien.jpg')]);

        $depense = Depense::firstOrFail();
        $ancien  = $depense->justificatif_fichier;

        $this->actingAs($this->admin)->put(route('dashboard.depenses.update', $depense), [
            'description'          => 'Achat de style',
            'categorie'            => 'equipement',
            'montant'              => 15000,
            'date'                 => now()->toDateString(),
            'justificatif_fichier' => UploadedFile::fake()->image('nouveau.jpg'),
        ])->assertRedirect();

        $depense->refresh();

        $this->assertNotSame($ancien, $depense->justificatif_fichier);
        Storage::disk('public')->assertMissing($ancien);
        Storage::disk('public')->assertExists($depense->justificatif_fichier);
    }

    public function test_supprimer_la_depense_supprime_le_fichier(): void
    {
        $this->depot(['justificatif_fichier' => UploadedFile::fake()->image('recu.jpg')]);

        $depense = Depense::firstOrFail();
        $chemin  = $depense->justificatif_fichier;

        $this->actingAs($this->admin)
            ->delete(route('dashboard.depenses.destroy', $depense))
            ->assertRedirect();

        Storage::disk('public')->assertMissing($chemin);
    }

    public function test_le_filtre_ne_montre_que_les_depenses_a_completer(): void
    {
        $this->depot(['description' => 'Sans rien']);
        $this->depot(['description' => 'Avec référence', 'justificatif' => 'Reçu 12']);

        $this->actingAs($this->admin)
            ->get(route('dashboard.finances.index', ['sans_justificatif' => 1]))
            ->assertOk()
            ->assertSee('Sans rien')
            ->assertDontSee('Avec référence');
    }

    public function test_le_formulaire_propose_une_reference_et_une_piece_jointe(): void
    {
        $this->actingAs($this->admin)
            ->get(route('dashboard.finances.index'))
            ->assertOk()
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('name="justificatif"', false)
            ->assertSee('name="justificatif_fichier"', false)
            ->assertSee('Référence du justificatif')
            ->assertSee('Photo ou PDF du reçu');
    }

    public function test_l_export_csv_contient_la_reference_et_le_lien(): void
    {
        $this->depot([
            'justificatif'         => 'Reçu n° 412',
            'justificatif_fichier' => UploadedFile::fake()->image('recu.jpg'),
        ]);

        $reponse = $this->actingAs($this->admin)
            ->get(route('dashboard.finances.export-depenses'));

        $reponse->assertOk();

        $csv = $reponse->streamedContent();

        $this->assertStringContainsString('Référence justificatif', $csv);
        $this->assertStringContainsString('Pièce jointe', $csv);
        $this->assertStringContainsString('Reçu n° 412', $csv);
        $this->assertStringContainsString('justificatifs/', $csv);
    }
}
