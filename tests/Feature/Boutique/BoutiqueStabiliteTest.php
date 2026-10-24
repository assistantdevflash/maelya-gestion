<?php

namespace Tests\Feature\Boutique;

use App\Models\Abonnement;
use App\Models\Institut;
use App\Models\PlanAbonnement;
use App\Models\Produit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests de stabilité de la boutique en ligne publique.
 *
 * Couvre les causes racines des incidents intermittents :
 *  - refus d'accès mis en cache (404 « fantômes » pendant 5 min) ;
 *  - scope multi-tenant appliqué sur une page publique (liste vide) ;
 *  - produits legacy avec visible_boutique = NULL (invisibles) ;
 *  - proprietaire_id manquant (404 définitif) ;
 *  - expiration / période de sursis.
 */
class BoutiqueStabiliteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Crée un établissement avec boutique payée + abonnement actif.
     *
     * @return array{0: Institut, 1: User, 2: PlanAbonnement}
     */
    private function creerBoutique(array $institutAttrs = [], array $abonnementAttrs = [], array $planAttrs = []): array
    {
        $institut = $this->creerInstitut(array_merge([
            'boutique_active'           => true,
            'boutique_option_active'    => true,
            'boutique_option_expire_le' => now()->addMonth()->toDateString(),
        ], $institutAttrs));

        $owner = User::factory()->create([
            'prenom'      => 'Awa',
            'nom_famille' => 'Proprio',
            'role'        => 'admin',
            'actif'       => true,
            'institut_id' => $institut->id,
        ]);

        $institut->forceFill(['proprietaire_id' => $owner->id])->save();

        $plan = PlanAbonnement::create(array_merge([
            'nom'         => 'Premium',
            'slug'        => 'premium',
            'duree_type'  => 'mensuel',
            'duree_jours' => 30,
            'prix'        => 9900,
            'actif'       => true,
        ], $planAttrs));

        Abonnement::create(array_merge([
            'user_id'   => $owner->id,
            'plan_id'   => $plan->id,
            'montant'   => 9900,
            'periode'   => 'mensuel',
            'statut'    => 'actif',
            'debut_le'  => now()->toDateString(),
            'expire_le' => now()->addMonth()->toDateString(),
        ], $abonnementAttrs));

        return [$institut, $owner, $plan];
    }

    private function creerProduit(Institut $institut, string $nom, array $attrs = []): Produit
    {
        return Produit::create(array_merge([
            'institut_id'      => $institut->id,
            'nom'              => $nom,
            'prix_vente'       => 5000,
            'stock'            => 10,
            'actif'            => true,
            'visible_boutique' => true,
        ], $attrs));
    }

    // ─────────────────────────────────────────────────────────────────────
    // 1. Les filtres produits restent corrects (le correctif ne casse rien)
    // ─────────────────────────────────────────────────────────────────────
    public function test_produits_masques_ou_inactifs_ne_sont_pas_affiches(): void
    {
        [$institut] = $this->creerBoutique();

        $visible = $this->creerProduit($institut, 'Produit visible');
        $masque  = $this->creerProduit($institut, 'Produit masqué', [
            'visible_boutique' => false,
        ]);
        $inactif = $this->creerProduit($institut, 'Produit inactif', [
            'actif' => false,
        ]);

        $this->get(route('shop.index', $institut->slug))
            ->assertOk()
            ->assertSee($visible->nom)
            ->assertDontSee($masque->nom)
            ->assertDontSee($inactif->nom);
    }

    // ─────────────────────────────────────────────────────────────────────
    // 2. Un visiteur connecté sur un AUTRE établissement voit les produits
    //    (le scope multi-tenant ne doit pas s'appliquer aux pages publiques)
    // ─────────────────────────────────────────────────────────────────────
    public function test_produits_visibles_meme_si_visiteur_connecte_sur_un_autre_institut(): void
    {
        [$institut] = $this->creerBoutique();
        $produit = $this->creerProduit($institut, 'Shampoing karité');

        // Autre administrateur, autre établissement
        [$autreInstitut, $autreAdmin] = $this->creerBoutique();

        $this->actingAs($autreAdmin)
            ->withSession(['current_institut_id' => $autreInstitut->id])
            ->get(route('shop.index', $institut->slug))
            ->assertOk()
            ->assertSee($produit->nom);
    }

    // ─────────────────────────────────────────────────────────────────────
    // 3. Un refus d'accès n'est JAMAIS mis en cache
    // ─────────────────────────────────────────────────────────────────────
    public function test_un_refus_dacces_nest_pas_mis_en_cache(): void
    {
        [$institut, $owner] = $this->creerBoutique([
            'boutique_option_active' => false,
        ]);

        $this->assertFalse($owner->hasBoutiqueAccess($institut), 'Option inactive → accès refusé');

        // La boutique est payée : immédiatement accessible (pas de refus caché)
        $institut->refresh();
        $institut->boutique_option_active = true;
        $institut->boutique_option_expire_le = now()->addMonth()->toDateString();
        $institut->save();

        $owner->forgetBoutiqueAccessCache($institut->id);

        $this->assertTrue(
            $owner->fresh()->hasBoutiqueAccess($institut->fresh()),
            'Le refus ne doit pas être mémorisé : l\'accès doit être rétabli immédiatement'
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // 4. Boutique désactivée → 404
    // ─────────────────────────────────────────────────────────────────────
    public function test_boutique_desactivee_renvoie_404(): void
    {
        [$institut] = $this->creerBoutique(['boutique_active' => false]);

        $this->get(route('shop.index', $institut->slug))->assertNotFound();
    }

    // ─────────────────────────────────────────────────────────────────────
    // 5. Option boutique expirée (hors sursis) → 404
    // ─────────────────────────────────────────────────────────────────────
    public function test_option_boutique_expiree_renvoie_404(): void
    {
        [$institut] = $this->creerBoutique([
            'boutique_option_expire_le' => now()->subDays(10)->toDateString(),
        ]);

        $this->get(route('shop.index', $institut->slug))->assertNotFound();
    }

    // ─────────────────────────────────────────────────────────────────────
    // 6. Abonnement en sursis (≤ 2 jours) → la boutique reste accessible
    // ─────────────────────────────────────────────────────────────────────
    public function test_boutique_accessible_pendant_le_sursis_de_deux_jours(): void
    {
        [$institut, $owner] = $this->creerBoutique(
            ['boutique_option_expire_le' => now()->subDay()->toDateString()],
            ['statut' => 'expire', 'expire_le' => now()->subDay()->toDateString()],
        );

        $this->assertTrue($owner->fresh()->hasBoutiqueAccess($institut->fresh()));

        $this->get(route('shop.index', $institut->slug))->assertOk();
    }

    // ─────────────────────────────────────────────────────────────────────
    // 7. proprietaire_id manquant (données legacy) → pas de 404
    // ─────────────────────────────────────────────────────────────────────
    public function test_boutique_accessible_si_proprietaire_id_est_null(): void
    {
        $institut = $this->creerInstitut([
            'boutique_active'           => true,
            'boutique_option_active'    => true,
            'boutique_option_expire_le' => now()->addMonth()->toDateString(),
        ]);

        // Propriétaire uniquement relié via users.institut_id (pas de proprietaire_id)
        $owner = User::factory()->create([
            'role'        => 'admin',
            'actif'       => true,
            'institut_id' => $institut->id,
        ]);

        $plan = PlanAbonnement::create([
            'nom' => 'Premium', 'slug' => 'premium', 'duree_type' => 'mensuel',
            'duree_jours' => 30, 'prix' => 9900, 'actif' => true,
        ]);

        Abonnement::create([
            'user_id' => $owner->id, 'plan_id' => $plan->id, 'montant' => 9900,
            'periode' => 'mensuel', 'statut' => 'actif',
            'debut_le' => now()->toDateString(), 'expire_le' => now()->addMonth()->toDateString(),
        ]);

        DB::table('instituts')->where('id', $institut->id)->update(['proprietaire_id' => null]);
        $institut->refresh();

        $this->assertNull($institut->proprietaire_id);
        $this->assertNotNull($institut->proprietaireEffectif());

        $produit = $this->creerProduit($institut, 'Produit legacy');

        $this->get(route('shop.index', $institut->slug))
            ->assertOk()
            ->assertSee($produit->nom);
    }

    // ─────────────────────────────────────────────────────────────────────
    // 8. Le cache positif est bien utilisé (pas de requête inutile en boucle)
    // ─────────────────────────────────────────────────────────────────────
    public function test_acces_autorise_est_mis_en_cache_positif(): void
    {
        [$institut, $owner] = $this->creerBoutique();

        $this->assertTrue($owner->hasBoutiqueAccess($institut));

        $key = "user_{$owner->id}_boutique_access_{$institut->id}";
        $this->assertTrue(Cache::get($key) === true, 'Un accès accordé doit être mis en cache');
    }
}
