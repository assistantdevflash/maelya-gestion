<?php

namespace Tests\Feature\Dashboard;

use App\Models\CategoriePrestation;
use App\Models\Prestation;
use App\Models\Vente;
use App\Services\TicketPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le ticket PDF est généré une seule fois puis servi depuis le disque.
 * C'est ce qui évite d'attendre dompdf à chaque impression.
 */
class TicketPdfTest extends TestCase
{
    use RefreshDatabase;

    private function creerVente(): Vente
    {
        $admin = $this->creerAdmin();

        $cat = CategoriePrestation::create([
            'institut_id' => $admin->institut_id,
            'nom'         => 'Coiffure',
            'ordre'       => 1,
        ]);

        $prestation = Prestation::create([
            'institut_id'  => $admin->institut_id,
            'categorie_id' => $cat->id,
            'nom'          => 'Coupe',
            'prix'         => 5000,
            'duree'        => 30,
            'actif'        => true,
        ]);

        $this->actingAs($admin)->post(route('dashboard.ventes.store'), [
            'panier_json' => json_encode([[
                'type'     => 'prestation',
                'id'       => $prestation->id,
                'nom'      => 'Coupe',
                'prix'     => 5000,
                'quantite' => 1,
            ]]),
            'mode_paiement' => 'cash',
            'total'         => 5000,
        ])->assertRedirect();

        return Vente::firstOrFail();
    }

    public function test_le_ticket_est_genere_puis_servi_depuis_le_cache(): void
    {
        $vente = $this->creerVente();
        $service = app(TicketPdfService::class);

        $chemin = $service->generer($vente);
        $this->assertFileExists($chemin);
        $this->assertStringStartsWith('%PDF', file_get_contents($chemin));

        // On remplace le fichier par un marqueur : si le service régénérait,
        // le marqueur disparaîtrait.
        file_put_contents($chemin, '%PDF-CACHE-TEST');
        $service->generer($vente);

        $this->assertSame('%PDF-CACHE-TEST', file_get_contents($chemin), 'Le PDF ne doit pas être régénéré.');

        @unlink($chemin);
    }

    public function test_l_annulation_supprime_le_ticket_en_cache(): void
    {
        $vente = $this->creerVente();
        $service = app(TicketPdfService::class);

        $chemin = $service->generer($vente);
        $this->assertFileExists($chemin);

        $service->oublier($vente);
        $this->assertFileDoesNotExist($chemin);
    }

    public function test_la_route_du_ticket_sert_un_pdf(): void
    {
        $vente = $this->creerVente();
        $admin = $vente->user;

        $reponse = $this->actingAs($admin)->get(route('dashboard.ventes.ticket-pdf', $vente));

        $reponse->assertOk();
        $this->assertSame('application/pdf', $reponse->headers->get('Content-Type'));
        $this->assertStringContainsString('inline', $reponse->headers->get('Content-Disposition'));

        @unlink(app(TicketPdfService::class)->chemin($vente));
    }
}
