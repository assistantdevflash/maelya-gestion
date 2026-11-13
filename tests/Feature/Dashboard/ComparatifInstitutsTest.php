<?php

namespace Tests\Feature\Dashboard;

use App\Models\Client;
use App\Models\Institut;
use App\Models\Vente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Comparatif multi-établissements : cohérence des chiffres et période.
 */
class ComparatifInstitutsTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private Institut $second;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->creerAdmin(['role' => 'admin']);

        // Deuxième établissement du même propriétaire
        $this->second = Institut::create([
            'nom'             => 'Brico Deco',
            'email'           => 'brico-' . uniqid() . '@test.com',
            'telephone'       => '0102030405',
            'ville'           => 'Abidjan',
            'type'            => 'commerce',
            'actif'           => true,
            'proprietaire_id' => $this->admin->id,
        ]);
    }

    private function vente(Institut $institut, array $attrs = []): Vente
    {
        // created_at n'est pas « fillable » : on le force après insertion.
        $date = $attrs['created_at'] ?? now();
        unset($attrs['created_at'], $attrs['updated_at']);

        $vente = Vente::create(array_merge([
            'institut_id'   => $institut->id,
            'user_id'       => $this->admin->id,
            'total'         => 10000,
            'mode_paiement' => 'cash',
            'statut'        => 'validee',
        ], $attrs));

        \DB::table('ventes')->where('id', $vente->id)->update([
            'created_at' => $date,
            'updated_at' => $date,
        ]);

        return $vente->refresh();
    }

    private function page(array $params = [])
    {
        return $this->actingAs($this->admin)->get(route('dashboard.comparatif.index', $params));
    }

    public function test_la_page_affiche_les_deux_etablissements(): void
    {
        $this->page()->assertOk()->assertSee('Brico Deco');
    }

    public function test_le_ca_d_une_vente_a_credit_ne_compte_que_l_encaisse(): void
    {
        // 50 000 F à crédit, seulement 10 000 F versés
        $this->vente($this->second, [
            'total'         => 50000,
            'mode_paiement' => 'credit',
            'montant_paye'  => 10000,
        ]);

        $reponse = $this->page();
        $stats   = collect($reponse->viewData('stats'))->keyBy(fn ($s) => $s['institut']->nom);

        $this->assertSame(10000, $stats['Brico Deco']['ca'], 'Seul le montant encaissé compte.');
        $this->assertSame(50000, (int) Vente::withoutGlobalScopes()
            ->where('institut_id', $this->second->id)->sum('total'), 'Le total de la vente reste 50 000.');
    }

    public function test_les_ventes_annulees_sont_exclues_du_ca_mais_comptees(): void
    {
        $this->vente($this->second, ['total' => 20000]);
        $this->vente($this->second, ['total' => 8000, 'statut' => 'annulee']);

        $stats = collect($this->page()->viewData('stats'))->keyBy(fn ($s) => $s['institut']->nom);
        $ligne = $stats['Brico Deco'];

        $this->assertSame(20000, $ligne['ca']);
        $this->assertSame(1, $ligne['nb_ventes']);
        $this->assertSame(1, $ligne['nb_annulees']);
        $this->assertEquals(50.0, $ligne['taux_annulation']);
    }

    public function test_les_clients_archives_ne_sont_pas_comptes(): void
    {
        Client::create([
            'institut_id' => $this->second->id,
            'type_client' => 'personne_physique',
            'prenom'      => 'Actif',
            'nom'         => 'Client',
            'telephone'   => '0700000001',
            'actif'       => true,
        ]);
        Client::create([
            'institut_id' => $this->second->id,
            'type_client' => 'personne_physique',
            'prenom'      => 'Archivé',
            'nom'         => 'Client',
            'telephone'   => '0700000002',
            'actif'       => false,
        ]);

        $stats = collect($this->page()->viewData('stats'))->keyBy(fn ($s) => $s['institut']->nom);

        $this->assertSame(1, $stats['Brico Deco']['nb_clients']);
    }

    public function test_la_periode_7_jours_exclut_les_ventes_anciennes(): void
    {
        $this->vente($this->second, ['total' => 15000, 'created_at' => now()->subDays(40), 'updated_at' => now()->subDays(40)]);
        $this->vente($this->second, ['total' => 5000]);

        $stats = collect($this->page(['periode' => '7j'])->viewData('stats'))->keyBy(fn ($s) => $s['institut']->nom);

        $this->assertSame(5000, $stats['Brico Deco']['ca']);
        $this->assertSame(1, $stats['Brico Deco']['nb_ventes']);
    }

    public function test_l_evolution_est_calculee_par_rapport_a_la_periode_precedente(): void
    {
        // Période courante (7 derniers jours) : 10 000
        $this->vente($this->second, ['total' => 10000]);

        // Période précédente (les 7 jours d'avant) : 5 000
        $this->vente($this->second, [
            'total'      => 5000,
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ]);

        $stats = collect($this->page(['periode' => '7j'])->viewData('stats'))->keyBy(fn ($s) => $s['institut']->nom);

        $this->assertSame(10000, $stats['Brico Deco']['ca']);
        $this->assertSame(5000, $stats['Brico Deco']['ca_precedent']);
        $this->assertEquals(100.0, $stats['Brico Deco']['evolution']);
    }

    public function test_le_tri_par_ventes_reordonne_les_lignes(): void
    {
        $this->vente($this->admin->institut, ['total' => 1000]);
        $this->vente($this->second, ['total' => 1000]);
        $this->vente($this->second, ['total' => 1000]);

        $stats = $this->page(['tri' => 'ventes'])->viewData('stats');

        $this->assertSame('Brico Deco', $stats[0]['institut']->nom);
    }

    public function test_l_export_produit_un_csv(): void
    {
        $this->vente($this->second, ['total' => 12000]);

        $reponse = $this->actingAs($this->admin)->get(route('dashboard.comparatif.export'));

        $reponse->assertOk();
        $this->assertStringContainsString('text/csv', (string) $reponse->headers->get('Content-Type'));

        $contenu = $reponse->streamedContent();

        $this->assertStringContainsString('Établissement', $contenu);
        $this->assertStringContainsString('Brico Deco', $contenu);
    }
}
