<?php

namespace Tests\Feature\Dashboard;

use App\Models\Commande;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Liste des commandes boutique.
 *
 * Bug corrigé : un `</div>` manquait pour fermer le `.card-body` du formulaire
 * de filtres. Le bloc suivant (carte + tableau des commandes) se retrouvait
 * alors **imbriqué** dans ce `.card-body` : les deux cartes paraissaient collées
 * et l'espacement `space-y-8` du conteneur ne s'appliquait plus.
 */
class CommandesIndexTest extends TestCase
{
    use RefreshDatabase;

    private function pageAvecUneCommande(): string
    {
        $admin = $this->creerAdmin();

        Commande::create([
            'institut_id'      => $admin->institut_id,
            'numero'           => 'CMD-TEST-' . uniqid(),
            'client_prenom'    => 'Basile',
            'client_nom'       => 'Tie',
            'client_telephone' => '0748606366',
            'client_adresse'   => 'Bingerville',
            'sous_total'       => 5000,
            'frais_livraison'  => 1500,
            'total'            => 6500,
            'statut'           => 'nouvelle',
            'mode_paiement'    => 'cash',
            'payee'            => false,
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard.boutique.commandes.index'));
        $response->assertOk();

        return $response->getContent();
    }

    public function test_les_balises_div_sont_equilibrees(): void
    {
        $html = $this->pageAvecUneCommande();

        $this->assertSame(
            substr_count($html, '<div'),
            substr_count($html, '</div>'),
            'Nombre de <div> et de </div> différent : une balise n\'est pas fermée.'
        );
    }

    public function test_la_carte_des_commandes_suit_directement_le_formulaire(): void
    {
        $html = $this->pageAvecUneCommande();

        $debutFormulaire = strpos($html, '<form method="GET"');
        $this->assertNotFalse($debutFormulaire, 'Formulaire de filtres introuvable.');

        $finFormulaire = strpos($html, '</form>', $debutFormulaire);
        $this->assertNotFalse($finFormulaire, 'Formulaire de filtres non fermé.');

        // Aucun conteneur intermédiaire : la carte doit être une soeur du
        // formulaire, dans le `.space-y-8` commun.
        $apres = substr($html, $finFormulaire, 200);
        $this->assertMatchesRegularExpression(
            '/<\/form>\s*<div class="card">/',
            $apres,
            'La carte des commandes n\'est pas directement après le formulaire (imbrication).'
        );

        $this->assertStringContainsString('class="space-y-8"', $html);
    }
}
