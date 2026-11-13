<?php

namespace Tests\Feature\Mail;

use App\Mail\FactureARelancer;
use App\Mail\RelanceCredit;
use App\Models\Credit;
use App\Models\Facture;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Les vues d'e-mail doivent être réellement rendues.
 *
 * Les tests qui utilisent `Mail::fake()` n'exécutent jamais le template : une
 * variable réservée par Laravel (comme `$message`, qui contient l'instance
 * Illuminate\Mail\Message) passe donc inaperçue jusqu'à la production.
 */
class RelanceRenduTest extends TestCase
{
    use RefreshDatabase;

    private function creerFacture($admin): Facture
    {
        $this->actingAs($admin)->post(route('dashboard.factures.store'), [
            'client_prenom'    => 'Awa',
            'client_nom'       => 'Traoré',
            'client_telephone' => '0701020304',
            'date_emission'    => now()->toDateString(),
            'date_echeance'    => now()->addDays(30)->toDateString(),
            'lignes'           => json_encode([[
                'designation'   => 'Coupe homme',
                'quantite'      => 2,
                'prix_unitaire' => 5000,
            ]]),
            'tva_applicable'   => 0,
        ])->assertRedirect();

        return Facture::withoutGlobalScopes()->orderByDesc('numero')->firstOrFail();
    }

    public function test_l_email_de_relance_de_facture_se_rend(): void
    {
        $admin   = $this->creerAdmin();
        $facture = $this->creerFacture($admin);

        $html = (new FactureARelancer($facture))->render();

        $this->assertStringContainsString('reste impayée', $html);
        $this->assertStringContainsString($facture->numero, $html);
    }

    public function test_l_email_de_relance_de_facture_accepte_un_message_personnalise(): void
    {
        $admin   = $this->creerAdmin();
        $facture = $this->creerFacture($admin);

        $html = (new FactureARelancer($facture, 'Merci de régler avant vendredi.'))->render();

        $this->assertStringContainsString('Merci de régler avant vendredi.', $html);
    }

    public function test_l_email_de_relance_de_credit_se_rend(): void
    {
        $admin = $this->creerAdmin();

        $client = \App\Models\Client::create([
            'institut_id' => $admin->institut_id,
            'type_client' => 'personne_physique',
            'prenom'      => 'Awa',
            'nom'         => 'Traoré',
            'telephone'   => '0701020304',
            'actif'       => true,
        ]);

        $vente = \App\Models\Vente::create([
            'institut_id'   => $admin->institut_id,
            'user_id'       => $admin->id,
            'total'         => 50000,
            'mode_paiement' => 'credit',
            'statut'        => 'validee',
        ]);

        $credit = Credit::create([
            'institut_id'     => $admin->institut_id,
            'vente_id'        => $vente->id,
            'client_id'       => $client->id,
            'montant_total'   => 50000,
            'montant_paye'    => 10000,
            'reste_a_payer'   => 40000,
            'nb_echeances'    => 3,
            'frequence'       => 'mensuelle',
            'date_debut'      => now()->subMonth()->toDateString(),
            'statut'          => 'en_cours',
        ]);

        $html = (new RelanceCredit($credit, 'Petit rappel amical.'))->render();

        $this->assertStringContainsString('Petit rappel amical.', $html);
    }
}
