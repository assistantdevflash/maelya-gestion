<?php

namespace Tests\Feature\Dashboard;

use App\Models\CategoriePrestation;
use App\Models\Prestation;
use App\Models\Vente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Historique des ventes : recherche, filtres, synthèse et export.
 */
class HistoriqueVentesTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private Prestation $prestation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->creerAdmin();

        $cat = CategoriePrestation::create([
            'institut_id' => $this->admin->institut_id,
            'nom'         => 'Coiffure',
            'ordre'       => 1,
        ]);

        $this->prestation = Prestation::create([
            'institut_id'  => $this->admin->institut_id,
            'categorie_id' => $cat->id,
            'nom'          => 'Coupe',
            'prix'         => 5000,
            'duree'        => 30,
            'actif'        => true,
        ]);
    }

    private function vendre(int $nombre = 1, ?string $date = null): void
    {
        for ($i = 0; $i < $nombre; $i++) {
            $this->actingAs($this->admin)->post(route('dashboard.ventes.store'), [
                'panier_json' => json_encode([[
                    'type'     => 'prestation',
                    'id'       => $this->prestation->id,
                    'nom'      => $this->prestation->nom,
                    'prix'     => 5000,
                    'quantite' => 1,
                ]]),
                'mode_paiement' => 'cash',
                'total'         => 5000,
            ])->assertRedirect();
        }

        if ($date !== null) {
            Vente::query()->update(['created_at' => $date]);
        }
    }

    public function test_la_synthese_porte_sur_tout_le_filtre_et_pas_sur_la_page(): void
    {
        // 30 ventes de 5 000 F, page limitée à 25
        $this->vendre(30);

        $reponse = $this->actingAs($this->admin)->get(route('dashboard.ventes.index', ['par_page' => 25]));

        $reponse->assertOk();

        $synthese = $reponse->viewData('synthese');

        $this->assertSame(30, (int) $synthese->nb_valides, 'La synthèse doit compter les 30 ventes.');
        $this->assertSame(150000, (int) $synthese->total_encaisse, 'Le total doit couvrir toutes les ventes.');

        // La page, elle, n'en contient que 25
        $this->assertCount(25, $reponse->viewData('ventes'));
    }

    public function test_la_recherche_par_numero_trouve_la_vente(): void
    {
        $this->vendre(3);
        $cible = Vente::orderBy('numero')->firstOrFail();

        $reponse = $this->actingAs($this->admin)
            ->get(route('dashboard.ventes.index', ['q' => $cible->numero]));

        $reponse->assertOk();
        $this->assertCount(1, $reponse->viewData('ventes'));
        $this->assertSame($cible->id, $reponse->viewData('ventes')->first()->id);
    }

    public function test_la_recherche_par_montant_fonctionne(): void
    {
        $this->vendre(2);

        $reponse = $this->actingAs($this->admin)
            ->get(route('dashboard.ventes.index', ['q' => '5000']));

        $reponse->assertOk();
        $this->assertCount(2, $reponse->viewData('ventes'));
    }

    public function test_le_filtre_statut_isole_les_ventes_annulees(): void
    {
        $this->vendre(2);
        $vente = Vente::firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('dashboard.ventes.annuler', $vente), ['motif_annulation' => 'Erreur'])
            ->assertRedirect();

        $annulees = $this->actingAs($this->admin)
            ->get(route('dashboard.ventes.index', ['statut' => 'annulees']));

        $this->assertCount(1, $annulees->viewData('ventes'));
        $this->assertSame($vente->id, $annulees->viewData('ventes')->first()->id);

        $payees = $this->actingAs($this->admin)
            ->get(route('dashboard.ventes.index', ['statut' => 'payees']));

        $this->assertCount(1, $payees->viewData('ventes'));
    }

    public function test_le_tri_par_montant_place_la_plus_grosse_vente_en_tete(): void
    {
        $this->vendre(1);

        // Une vente à 20 000 F
        $this->actingAs($this->admin)->post(route('dashboard.ventes.store'), [
            'panier_json' => json_encode([[
                'type'     => 'prestation',
                'id'       => $this->prestation->id,
                'nom'      => $this->prestation->nom,
                'prix'     => 5000,
                'quantite' => 4,
            ]]),
            'mode_paiement' => 'cash',
            'total'         => 20000,
        ])->assertRedirect();

        $reponse = $this->actingAs($this->admin)
            ->get(route('dashboard.ventes.index', ['tri' => 'montant']));

        $this->assertSame(20000, (int) $reponse->viewData('ventes')->first()->total);
    }

    public function test_l_export_produit_un_csv_filtre(): void
    {
        $this->vendre(2);

        $reponse = $this->actingAs($this->admin)->get(route('dashboard.ventes.export'));

        $reponse->assertOk();
        $this->assertStringContainsString('text/csv', (string) $reponse->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', (string) $reponse->headers->get('Content-Disposition'));

        $contenu = $reponse->streamedContent();

        $this->assertStringContainsString('N° vente', $contenu);
        $this->assertStringContainsString('Espèces', $contenu);
    }

    public function test_le_detail_affiche_l_auteur_de_l_annulation(): void
    {
        $this->vendre(1);
        $vente = Vente::firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('dashboard.ventes.annuler', $vente), ['motif_annulation' => 'Client absent'])
            ->assertRedirect();

        $reponse = $this->actingAs($this->admin)->get(route('dashboard.ventes.show', $vente));

        $reponse->assertOk()
            ->assertSee('Client absent')
            ->assertSee($this->admin->prenom);
    }

    public function test_le_detail_navigue_entre_les_ventes(): void
    {
        $this->vendre(3);
        $ventes = Vente::orderBy('created_at')->get();

        $reponse = $this->actingAs($this->admin)->get(route('dashboard.ventes.show', $ventes[1]));

        $reponse->assertOk();
        $this->assertSame($ventes[0]->id, $reponse->viewData('precedente')->id);
        $this->assertSame($ventes[2]->id, $reponse->viewData('suivante')->id);
    }

    public function test_la_ligne_de_l_historique_contient_un_vrai_lien(): void
    {
        $this->vendre(1);
        $vente = Vente::firstOrFail();

        $reponse = $this->actingAs($this->admin)->get(route('dashboard.ventes.index'));

        $html = $reponse->getContent();

        $this->assertStringContainsString(route('dashboard.ventes.show', $vente), $html);
        // Plus de navigation via un onclick sur la ligne
        $this->assertStringNotContainsString("onclick=\"window.location.href='", $html);
    }
}
