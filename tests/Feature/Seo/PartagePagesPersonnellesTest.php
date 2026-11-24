<?php

namespace Tests\Feature\Seo;

use App\Models\AvisClient;
use App\Models\Client;
use App\Models\Credit;
use App\Models\Institut;
use App\Models\Vente;
use App\Services\CreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Aperçus de partage des pages personnelles : carte de fidélité, suivi de
 * crédit et sondage d'avis.
 *
 * Ces liens sont envoyés au client par WhatsApp ou SMS. Sans balises, le
 * message n'affichait qu'une URL nue. Avec elles, il faut en revanche éviter
 * deux écueils : indexer une page privée, et divulguer des données
 * personnelles dans un aperçu qui peut être transféré.
 */
class PartagePagesPersonnellesTest extends TestCase
{
    use RefreshDatabase;

    private Institut $institut;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $admin = $this->creerAdmin(['prenom' => 'Marie', 'nom_famille' => 'Admin']);
        $this->institut = Institut::findOrFail($admin->institut_id);

        $this->client = Client::create([
            'institut_id'   => $this->institut->id,
            'prenom'        => 'Eva',
            'nom'           => 'Fidele',
            'telephone'     => '0700000001',
            'actif'         => true,
            'points_fidelite' => 4242,
        ]);
    }

    private function poserLogoEtablissement(): string
    {
        Storage::disk('public')->put('logos/etablissement.png', 'contenu-image');
        $this->institut->update(['logo' => 'logos/etablissement.png']);

        return Storage::disk('public')->url('logos/etablissement.png');
    }

    /**
     * Extrait le contenu d'une balise meta par sa propriété ou son nom.
     */
    private function meta(string $html, string $cle, string $attribut = 'property'): ?string
    {
        preg_match('/<' . 'meta ' . $attribut . '="' . preg_quote($cle, '/') . '" content="(.*?)">/s', $html, $correspondances);

        return isset($correspondances[1]) ? html_entity_decode($correspondances[1], ENT_QUOTES) : null;
    }

    private function creerCredit(int $montant = 75000): Credit
    {
        $vente = Vente::create([
            'institut_id'   => $this->institut->id,
            'user_id'       => $this->institut->proprietaire_id,
            'client_id'     => $this->client->id,
            'numero'        => 'V-' . uniqid(),
            'total'         => $montant,
            'montant_paye'  => 0,
            'mode_paiement' => 'credit',
            'credit_statut' => 'en_cours',
            'statut'        => 'validee',
        ]);

        return Credit::create([
            'institut_id'    => $this->institut->id,
            'client_id'      => $this->client->id,
            'vente_id'       => $vente->id,
            'montant_total'  => $montant,
            'apport_initial' => 0,
            'reste_a_payer'  => $montant,
            'nb_echeances'   => 3,
            'frequence'      => 'mensuelle',
            'statut'         => 'en_cours',
            'date_debut'     => now(),
        ]);
    }

    // ── Carte de fidélité ────────────────────────────────────────────────────

    public function test_la_carte_de_fidelite_expose_un_apercu_de_partage(): void
    {
        $logo = $this->poserLogoEtablissement();

        $html = $this->get(route('public.carte-fidelite', $this->client->fidelite_token))
            ->assertOk()
            ->getContent();

        $this->assertSame($logo, $this->meta($html, 'og:image'));
        $this->assertSame($logo, $this->meta($html, 'twitter:image', 'name'));
        $this->assertNotNull($this->meta($html, 'og:title'));
        $this->assertSame('summary_large_image', $this->meta($html, 'twitter:card', 'name'));
    }

    public function test_la_carte_de_fidelite_n_est_pas_indexable(): void
    {
        $this->get(route('public.carte-fidelite', $this->client->fidelite_token))
            ->assertOk()
            ->assertSee('name="robots" content="noindex, nofollow"', false);
    }

    public function test_l_apercu_de_la_carte_ne_divulgue_pas_le_client_ni_son_solde(): void
    {
        $html = $this->get(route('public.carte-fidelite', $this->client->fidelite_token))
            ->assertOk()
            ->getContent();

        foreach (['og:title', 'og:description'] as $cle) {
            $valeur = (string) $this->meta($html, $cle);

            $this->assertStringNotContainsString('Eva', $valeur, 'Le prénom du client ne doit pas figurer dans l\'aperçu.');
            $this->assertStringNotContainsString('4242', $valeur, 'Le solde de points ne doit pas figurer dans l\'aperçu.');
        }

        $this->assertStringContainsString($this->institut->nom, (string) $this->meta($html, 'og:title'));
    }

    // ── Suivi de crédit ──────────────────────────────────────────────────────

    public function test_le_suivi_de_credit_expose_un_apercu_de_partage(): void
    {
        $logo = $this->poserLogoEtablissement();
        $credit = $this->creerCredit();

        $html = $this->get(app(CreditService::class)->urlSuivi($credit))
            ->assertOk()
            ->getContent();

        $this->assertSame($logo, $this->meta($html, 'og:image'));
        $this->assertSame($logo, $this->meta($html, 'twitter:image', 'name'));
        $this->assertStringContainsString($this->institut->nom, (string) $this->meta($html, 'og:title'));
    }

    public function test_le_suivi_de_credit_reste_non_indexable_et_sans_montant(): void
    {
        $credit = $this->creerCredit(75000);

        $html = $this->get(app(CreditService::class)->urlSuivi($credit))
            ->assertOk()
            ->assertSee('name="robots" content="noindex, nofollow"', false)
            ->getContent();

        foreach (['og:title', 'og:description'] as $cle) {
            $valeur = (string) $this->meta($html, $cle);

            $this->assertStringNotContainsString('Eva', $valeur);
            $this->assertStringNotContainsString('75', $valeur, 'Aucun montant ne doit apparaître dans l\'aperçu.');
        }
    }

    // ── Avis client ──────────────────────────────────────────────────────────

    private function creerAvis(): AvisClient
    {
        return AvisClient::create([
            'institut_id' => $this->institut->id,
            'statut'      => 'en_attente',
        ]);
    }

    public function test_le_formulaire_d_avis_expose_un_apercu_de_partage(): void
    {
        $logo = $this->poserLogoEtablissement();
        $avis = $this->creerAvis();

        $html = $this->get(route('public.avis.show', $avis->token))
            ->assertOk()
            ->assertSee('name="robots" content="noindex, nofollow"', false)
            ->getContent();

        $this->assertSame($logo, $this->meta($html, 'og:image'));
        $this->assertStringContainsString($this->institut->nom, (string) $this->meta($html, 'og:title'));
    }

    public function test_la_page_de_remerciement_expose_un_apercu_de_partage(): void
    {
        $logo = $this->poserLogoEtablissement();
        $avis = $this->creerAvis();

        $html = $this->post(route('public.avis.submit', $avis->token), ['note' => 5])
            ->assertOk()
            ->assertSee('name="robots" content="noindex, nofollow"', false)
            ->getContent();

        $this->assertSame($logo, $this->meta($html, 'og:image'));
    }

    public function test_la_page_avis_deja_repondu_expose_un_apercu_de_partage(): void
    {
        $logo = $this->poserLogoEtablissement();
        $avis = $this->creerAvis();

        // Deuxième visite du même lien : le sondage a déjà été rempli.
        $avis->forceFill(['note' => 4, 'repondu_le' => now()])->save();

        $html = $this->get(route('public.avis.show', $avis->token))
            ->assertOk()
            ->assertSee('name="robots" content="noindex, nofollow"', false)
            ->getContent();

        $this->assertSame($logo, $this->meta($html, 'og:image'));
    }

    public function test_sans_logo_l_apercu_retombe_sur_le_visuel_maelya(): void
    {
        $avis = $this->creerAvis();

        $html = $this->get(route('public.avis.show', $avis->token))->assertOk()->getContent();

        $this->assertStringContainsString('images/og-maelya.png', (string) $this->meta($html, 'og:image'));

        // Dimensions connues uniquement pour le visuel par défaut.
        $this->assertSame('1200', $this->meta($html, 'og:image:width'));
        $this->assertSame('630', $this->meta($html, 'og:image:height'));
    }
}
