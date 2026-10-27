<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Commande;
use App\Models\Depense;
use App\Models\Facture;
use App\Models\Institut;
use App\Models\RendezVous;
use App\Models\Vente;
use App\Models\VenteItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Construit le bilan financier mensuel d'un établissement :
 * chiffres du mois, comparaison avec le mois précédent et conseil marketing.
 */
class RapportMensuelService
{
    /**
     * @param  Carbon|null $mois Mois à analyser (défaut : mois écoulé)
     * @return array<string, mixed>
     */
    public function construire(Institut $institut, ?Carbon $mois = null): array
    {
        $mois   = ($mois ?? now()->subMonth())->copy()->startOfMonth();
        $debut  = $mois->copy()->startOfMonth();
        $fin    = $mois->copy()->endOfMonth();
        $debutPrecedent = $debut->copy()->subMonthNoOverflow()->startOfMonth();
        $finPrecedent   = $debutPrecedent->copy()->endOfMonth();

        $actuel    = $this->metriques($institut, $debut, $fin);
        $precedent = $this->metriques($institut, $debutPrecedent, $finPrecedent);

        $evolutions = [
            'ca'        => $this->evolution($actuel['ca'], $precedent['ca']),
            'depenses'  => $this->evolution($actuel['depenses'], $precedent['depenses']),
            'benefice'  => $this->evolution($actuel['benefice'], $precedent['benefice']),
            'ventes'    => $this->evolution($actuel['nb_ventes'], $precedent['nb_ventes']),
        ];

        $top = $this->topItems($institut, $debut, $fin);

        return [
            'institut'  => $institut,
            'debut'     => $debut,
            'fin'       => $fin,
            'label'     => ucfirst($debut->translatedFormat('F Y')),
            'label_precedent' => ucfirst($debutPrecedent->translatedFormat('F Y')),
            'actuel'    => $actuel,
            'precedent' => $precedent,
            'evolutions' => $evolutions,
            'top'       => $top,
            'depenses_categories' => $this->depensesParCategorie($institut, $debut, $fin),
            'sante'     => $this->sante($actuel),
            'conseil'   => $this->conseil($actuel, $evolutions, $top),
            'a_activite' => $actuel['nb_ventes'] > 0
                || $actuel['depenses'] > 0
                || $actuel['nouveaux_clients'] > 0
                || $actuel['rdv'] > 0,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // Métriques d'une période
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @return array<string, int|float>
     */
    private function metriques(Institut $institut, Carbon $debut, Carbon $fin): array
    {
        // Chiffre d'affaires : une vente à crédit ne compte que ce qui a été
        // encaissé. `credit_statut` est renseigné par les deux chemins de
        // création (CreditController et caisse) — plus précis que le mode.
        $ca = (int) Vente::where('institut_id', $institut->id)
            ->where('statut', 'validee')
            ->whereBetween('created_at', [$debut, $fin])
            ->selectRaw('COALESCE(SUM(CASE WHEN credit_statut IS NOT NULL THEN montant_paye ELSE total END), 0) as ca')
            ->value('ca');

        $nbVentes = Vente::where('institut_id', $institut->id)
            ->where('statut', 'validee')
            ->whereBetween('created_at', [$debut, $fin])
            ->where(function ($q) {
                // Un crédit sans apport n'a encore rien rapporté
                $q->whereNull('credit_statut')->orWhere('montant_paye', '>', 0);
            })
            ->count();

        $depenses = (int) Depense::where('institut_id', $institut->id)
            ->whereBetween('date', [$debut->toDateString(), $fin->toDateString()])
            ->sum('montant');

        $benefice = $ca - $depenses;
        $marge    = $ca > 0 ? round(($benefice / $ca) * 100, 1) : 0.0;

        $nouveauxClients = Client::where('institut_id', $institut->id)
            ->whereBetween('created_at', [$debut, $fin])
            ->count();

        $clientsServis = Vente::where('institut_id', $institut->id)
            ->where('statut', 'validee')
            ->whereBetween('created_at', [$debut, $fin])
            ->whereNotNull('client_id')
            ->distinct()
            ->count('client_id');

        $rdv = RendezVous::where('institut_id', $institut->id)
            ->whereBetween('debut_le', [$debut, $fin])
            ->count();

        $rdvTermines = RendezVous::where('institut_id', $institut->id)
            ->whereBetween('debut_le', [$debut, $fin])
            ->where('statut', 'termine')
            ->count();

        // Factures émises / effectivement réglées
        $factures = Facture::where('institut_id', $institut->id)
            ->whereBetween('date_emission', [$debut->toDateString(), $fin->toDateString()])
            ->count();

        $facturesReglees = Facture::where('institut_id', $institut->id)
            ->whereBetween('date_emission', [$debut->toDateString(), $fin->toDateString()])
            ->where('statut', 'payee')
            ->count();

        // Commandes boutique : reçues / livrées ET encaissées
        $commandes = Commande::where('institut_id', $institut->id)
            ->whereBetween('created_at', [$debut, $fin])
            ->count();

        $commandesLivreesPayees = Commande::where('institut_id', $institut->id)
            ->whereBetween('created_at', [$debut, $fin])
            ->where('statut', 'livree')
            ->where('payee', true)
            ->count();

        return [
            'ca'               => $ca,
            'nb_ventes'        => $nbVentes,
            'panier_moyen'     => $nbVentes > 0 ? (int) round($ca / $nbVentes) : 0,
            'depenses'         => $depenses,
            'benefice'         => $benefice,
            'marge'            => $marge,
            'nouveaux_clients' => $nouveauxClients,
            'clients_servis'   => $clientsServis,
            'rdv'              => $rdv,
            'rdv_termines'     => $rdvTermines,
            'factures'         => $factures,
            'factures_reglees' => $facturesReglees,
            'commandes'        => $commandes,
            'commandes_livrees_payees' => $commandesLivreesPayees,
        ];
    }

    /**
     * Meilleures prestations et produits du mois.
     *
     * @return array{prestations: array<int, array>, produits: array<int, array>}
     */
    private function topItems(Institut $institut, Carbon $debut, Carbon $fin): array
    {
        $base = fn (string $type) => VenteItem::query()
            ->join('ventes', 'ventes.id', '=', 'vente_items.vente_id')
            ->where('ventes.institut_id', $institut->id)
            ->where('ventes.statut', 'validee')
            ->whereBetween('ventes.created_at', [$debut, $fin])
            ->where('vente_items.type', $type)
            ->selectRaw('vente_items.nom_snapshot as nom, SUM(vente_items.quantite) as quantite, SUM(vente_items.sous_total) as ca')
            ->groupBy('vente_items.nom_snapshot')
            ->orderByDesc('ca')
            ->limit(3)
            ->get()
            ->map(fn ($l) => [
                'nom'      => $l->nom,
                'quantite' => (int) $l->quantite,
                'ca'       => (int) $l->ca,
            ])
            ->all();

        return [
            'prestations' => $base('prestation'),
            'produits'    => $base('produit'),
        ];
    }

    /**
     * Répartition des dépenses par catégorie, de la plus lourde à la plus légère.
     *
     * @return array<int, array{categorie: string, total: int, part: float}>
     */
    private function depensesParCategorie(Institut $institut, Carbon $debut, Carbon $fin): array
    {
        $lignes = Depense::where('institut_id', $institut->id)
            ->whereBetween('date', [$debut->toDateString(), $fin->toDateString()])
            ->selectRaw('categorie, SUM(montant) as total')
            ->groupBy('categorie')
            ->orderByDesc('total')
            ->get();

        $total = (int) $lignes->sum('total');

        return $lignes->map(fn ($l) => [
            'categorie' => $l->categorie ?: 'autres',
            'label'     => Depense::categorieLabel($l->categorie ?: 'autres'),
            'total'     => (int) $l->total,
            'part'      => $total > 0 ? round(((int) $l->total / $total) * 100, 1) : 0.0,
        ])->all();
    }

    // ─────────────────────────────────────────────────────────────────────
    // Analyse
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Variation en %.
     *
     * Retourne null quand la comparaison n'a pas de sens : un mois précédent
     * à zéro ne donne pas « +100 % » mais aucune référence exploitable.
     */
    private function evolution(int $actuel, int $precedent): ?float
    {
        if ($precedent === 0) {
            return null;
        }

        return round((($actuel - $precedent) / abs($precedent)) * 100, 1);
    }

    /** @return array{code: string, label: string} */
    private function sante(array $metriques): array
    {
        if ($metriques['benefice'] > 0) {
            return ['code' => 'positive', 'label' => 'Mois bénéficiaire'];
        }

        if ($metriques['ca'] === 0 && $metriques['depenses'] === 0) {
            return ['code' => 'neutre', 'label' => 'Aucune activité'];
        }

        return ['code' => 'negative', 'label' => 'Mois déficitaire'];
    }

    /**
     * Conseil marketing, choisi selon le signal le plus prioritaire.
     *
     * @return array{titre: string, texte: string, action: string, niveau: string}
     */
    private function conseil(array $actuel, array $evolutions, array $top): array
    {
        $ca       = $actuel['ca'];
        $depenses = $actuel['depenses'];
        $panier   = $actuel['panier_moyen'];
        $evoCa    = $evolutions['ca'];

        // 1. Mois sans aucune vente
        if ($ca === 0) {
            return [
                'niveau' => 'critique',
                'titre'  => 'Relancez votre activité',
                'texte'  => 'Aucune vente enregistrée ce mois-ci. Votre fichier client est votre meilleur atout : contactez les 10 dernières personnes venues chez vous, même pour un simple bonjour.',
                'action' => 'Proposez une offre de retour (-20 % sur la prochaine visite) aux clients absents depuis 2 mois.',
            ];
        }

        // 2. Baisse marquée
        if ($evoCa !== null && $evoCa <= -20) {
            return [
                'niveau' => 'alerte',
                'titre'  => 'Chiffre d\'affaires en repli de ' . abs($evoCa) . ' %',
                'texte'  => 'Le mois précédent était nettement meilleur. Une baisse de cette ampleur vient presque toujours d\'une baisse de fréquentation, pas d\'une baisse de qualité.',
                'action' => 'Envoyez un message WhatsApp à vos 20 clientes les plus fidèles avec une offre limitée à 10 jours.',
            ];
        }

        // 3. Charges trop lourdes
        if ($depenses > 0 && ($depenses / max(1, $ca)) > 0.5) {
            $part = round(($depenses / $ca) * 100);
            return [
                'niveau' => 'alerte',
                'titre'  => 'Vos charges absorbent ' . $part . ' % du chiffre d\'affaires',
                'texte'  => 'La rentabilité se joue autant sur les recettes que sur les dépenses. Repérez la catégorie la plus lourde dans le détail ci-dessous.',
                'action' => 'Renégociez ou regroupez vos achats fournisseurs : un gain de 10 % sur cette catégorie vaut une dizaine de ventes.',
            ];
        }

        // 4. Forte progression — la performance mérite d'être soulignée avant
        //    les ajustements tactiques
        if ($evoCa !== null && $evoCa >= 20) {
            return [
                'niveau' => 'positif',
                'titre'  => 'Excellente progression : +' . $evoCa . ' %',
                'texte'  => 'Votre chiffre d\'affaires a nettement progressé par rapport au mois précédent. C\'est le moment idéal pour transformer ces nouveaux clients en habitués.',
                'action' => 'Parlez du programme de fidélité à chaque encaissement : un client qui y adhère revient en moyenne plus souvent.',
            ];
        }

        // 5. Panier moyen faible
        if ($panier > 0 && $panier < 5000) {
            return [
                'niveau' => 'conseil',
                'titre'  => 'Votre panier moyen est de ' . number_format($panier, 0, ',', ' ') . ' F',
                'texte'  => 'Vos clientes viennent, mais repartent avec peu. C\'est le levier le plus rapide à activer : augmenter le panier moyen ne coûte aucun client supplémentaire.',
                'action' => 'Proposez systématiquement un produit complémentaire à l\'encaissement (« avec le soin, la crème protectrice est à -15 % »).',
            ];
        }

        // 6. Aucun nouveau client
        if ($actuel['nouveaux_clients'] === 0) {
            return [
                'niveau' => 'conseil',
                'titre'  => 'Aucun nouveau client ce mois-ci',
                'texte'  => 'Votre activité repose uniquement sur vos clients existants. C\'est un socle solide, mais il finit toujours par s\'éroder.',
                'action' => 'Partagez le lien de votre vitrine et de votre boutique en ligne sur WhatsApp et Facebook, et demandez à 3 clientes satisfaites de vous recommander.',
            ];
        }

        // 7. Dépendance à une seule prestation
        if (!empty($top['prestations'])) {
            $caTop = $top['prestations'][0]['ca'] ?? 0;
            if ($ca > 0 && ($caTop / $ca) > 0.6) {
                return [
                    'niveau' => 'conseil',
                    'titre'  => 'Plus de 60 % de votre chiffre d\'affaires tient à une seule prestation',
                    'texte'  => '« ' . $top['prestations'][0]['nom'] . ' » porte votre activité. Cette concentration est confortable, mais fragile.',
                    'action' => 'Créez un forfait qui associe cette prestation phare à une autre, moins demandée : vous augmentez le panier sans effort commercial.',
                ];
            }
        }

        // 8. Situation saine
        return [
            'niveau' => 'positif',
            'titre'  => 'Mois équilibré, continuez sur cette lancée',
            'texte'  => 'Votre activité se porte bien et vos charges restent maîtrisées. C\'est le bon moment pour préparer la suite sans pression.',
            'action' => 'Collectez des avis clients ce mois-ci : ils alimentent votre visibilité et rassurent les nouveaux visiteurs.',
        ];
    }
}
