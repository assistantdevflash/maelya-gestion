<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Credit;
use App\Models\Echeance;
use App\Models\Institut;
use App\Models\PaiementCredit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

/**
 * Logique métier du module « crédits clients ».
 *
 * Centralise : plafond d'encours, score de paiement, balance âgée, taux de
 * recouvrement, liens signés, messages de relance et opérations de
 * réaménagement (rééchelonnement / report d'échéance).
 */
class CreditService
{
    /** Seuil : une échéance devenue « en retard » après J+1. */
    public const JOURS_GRACE = 1;

    // ─────────────────────────────────────────────────────────────────────
    // Plafond d'encours
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Plafond applicable au client : le sien, sinon celui de l'établissement.
     * `null` = pas de plafond.
     */
    public function plafondEffectif(Client $client): ?int
    {
        if ($client->credit_plafond !== null) {
            return (int) $client->credit_plafond;
        }

        $institut = Institut::find($client->institut_id);

        return $institut?->credit_plafond_defaut !== null
            ? (int) $institut->credit_plafond_defaut
            : null;
    }

    /** Encours total du client (somme des reste à payer non soldés). */
    public function encoursClient(Client $client): int
    {
        return (int) Credit::where('client_id', $client->id)
            ->where('statut', '!=', 'solde')
            ->sum('reste_a_payer');
    }

    /**
     * Vérifie si un nouveau crédit ferait dépasser le plafond.
     *
     * @return array{depasse: bool, plafond: ?int, encours: int, apres: int, reste_disponible: ?int, message: ?string}
     */
    public function verifierPlafond(Client $client, int $montantCredit): array
    {
        $plafond = $this->plafondEffectif($client);
        $encours = $this->encoursClient($client);
        $apres   = $encours + max(0, $montantCredit);

        $depasse = $plafond !== null && $apres > $plafond;

        return [
            'depasse'          => $depasse,
            'plafond'          => $plafond,
            'encours'          => $encours,
            'apres'            => $apres,
            'reste_disponible' => $plafond !== null ? max(0, $plafond - $encours) : null,
            'message'          => $depasse
                ? 'Plafond d\'encours dépassé : '
                    . number_format($encours, 0, ',', ' ') . ' F déjà dû + '
                    . number_format($montantCredit, 0, ',', ' ') . ' F demandés, pour un plafond de '
                    . number_format($plafond, 0, ',', ' ') . ' F.'
                : null,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // Score de paiement
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Score de ponctualité (0-100) basé sur les échéances déjà échues.
     *
     * @return array{score: ?int, libelle: string, couleur: string, echues: int, a_temps: int, en_retard: int}
     */
    public function scoreClient(Client $client): array
    {
        $echues = Echeance::whereIn('credit_id', Credit::where('client_id', $client->id)->select('id'))
            ->whereDate('date_prevue', '<=', now()->toDateString())
            ->get(['statut', 'date_prevue', 'date_paiement', 'montant', 'montant_paye']);

        $total = $echues->count();

        if ($total === 0) {
            return [
                'score'     => null,
                'libelle'   => 'Nouveau client',
                'couleur'   => 'gray',
                'echues'    => 0,
                'a_temps'   => 0,
                'en_retard' => 0,
            ];
        }

        $aTemps = $echues->filter(function (Echeance $e) {
            return $e->date_paiement !== null
                && Carbon::parse($e->date_paiement)->lte(Carbon::parse($e->date_prevue));
        })->count();

        $enRetard = $echues->filter(fn (Echeance $e) => $e->statut !== 'payee')->count();

        $score = (int) round($aTemps / $total * 100);

        [$libelle, $couleur] = match (true) {
            $score >= 90 => ['Excellent', 'emerald'],
            $score >= 70 => ['Bon', 'lime'],
            $score >= 50 => ['Moyen', 'amber'],
            default      => ['Risqué', 'red'],
        };

        return [
            'score'     => $score,
            'libelle'   => $libelle,
            'couleur'   => $couleur,
            'echues'    => $total,
            'a_temps'   => $aTemps,
            'en_retard' => $enRetard,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // Pilotage du portefeuille
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Balance âgée des créances (échéances non soldées, y compris à venir).
     *
     * @return array{a_venir: int, t0_30: int, t31_60: int, t61_90: int, t90_plus: int, total: int, nb: int}
     */
    public function balanceAgee(string $institutId): array
    {
        $echeances = Echeance::where('institut_id', $institutId)
            ->where('statut', '!=', 'payee')
            ->whereIn('credit_id', Credit::where('institut_id', $institutId)->where('statut', '!=', 'solde')->select('id'))
            ->get(['date_prevue', 'montant', 'montant_paye']);

        $t = [
            'a_venir'  => 0,
            't0_30'    => 0,
            't31_60'   => 0,
            't61_90'   => 0,
            't90_plus' => 0,
            'total'    => 0,
            'nb'       => 0,
        ];

        $today = Carbon::today();

        foreach ($echeances as $e) {
            $reste = max(0, (int) $e->montant - (int) $e->montant_paye);
            if ($reste === 0) {
                continue;
            }

            $jours = $e->date_prevue ? Carbon::parse($e->date_prevue)->diffInDays($today, false) : 0;

            if ($jours < 0) {
                $t['a_venir'] += $reste;
            } elseif ($jours <= 30) {
                $t['t0_30'] += $reste;
            } elseif ($jours <= 60) {
                $t['t31_60'] += $reste;
            } elseif ($jours <= 90) {
                $t['t61_90'] += $reste;
            } else {
                $t['t90_plus'] += $reste;
            }

            $t['total'] += $reste;
            $t['nb']++;
        }

        return $t;
    }

    /** Taux de recouvrement global du portefeuille (0-100). */
    public function tauxRecouvrement(string $institutId): ?float
    {
        $row = Credit::where('institut_id', $institutId)
            ->selectRaw('COALESCE(SUM(montant_total), 0) as total, COALESCE(SUM(montant_total - reste_a_payer), 0) as encaisse')
            ->first();

        if (! $row || (int) $row->total === 0) {
            return null;
        }

        return round((int) $row->encaisse / (int) $row->total * 100, 1);
    }

    /** Délai moyen d'encaissement (en jours) sur les échéances payées. */
    public function delaiMoyenEncaissement(string $institutId): ?float
    {
        $rows = Echeance::where('institut_id', $institutId)
            ->where('statut', 'payee')
            ->whereNotNull('date_paiement')
            ->get(['date_prevue', 'date_paiement']);

        if ($rows->isEmpty()) {
            return null;
        }

        $total = $rows->sum(fn (Echeance $e) => Carbon::parse($e->date_prevue)->diffInDays(Carbon::parse($e->date_paiement), false));

        return round($total / $rows->count(), 1);
    }

    /**
     * Clients les plus endettés.
     *
     * @return \Illuminate\Support\Collection<int, array{client: Client, du: int, retards: int}>
     */
    public function topDebiteurs(string $institutId, int $limit = 5)
    {
        return Credit::where('institut_id', $institutId)
            ->where('statut', '!=', 'solde')
            ->selectRaw('client_id, SUM(reste_a_payer) as du, SUM(CASE WHEN statut = \'retard\' THEN 1 ELSE 0 END) as retards')
            ->groupBy('client_id')
            ->orderByDesc('du')
            ->limit($limit)
            ->with('client')
            ->get()
            ->map(fn ($row) => [
                'client'  => $row->client,
                'du'      => (int) $row->du,
                'retards' => (int) $row->retards,
            ])
            ->filter(fn ($row) => $row['client'] !== null)
            ->values();
    }

    // ─────────────────────────────────────────────────────────────────────
    // Liens partageables (signés : non falsifiables, sans expiration)
    // ─────────────────────────────────────────────────────────────────────

    public function urlFiche(Credit $credit): string
    {
        return URL::signedRoute('credit.fiche.public', ['id' => $credit->id]);
    }

    public function urlSuivi(Credit $credit): string
    {
        return URL::signedRoute('credit.suivi.public', ['id' => $credit->id]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Relances
    // ─────────────────────────────────────────────────────────────────────

    /** Numéro international pour wa.me (Côte d'Ivoire par défaut). */
    public function numeroWhatsApp(?string $telephone): ?string
    {
        if (! $telephone) {
            return null;
        }

        $tel = ltrim(preg_replace('/[^0-9+]/', '', $telephone), '+');

        if ($tel === '') {
            return null;
        }

        if (str_starts_with($tel, '0') && strlen($tel) === 10) {
            $tel = '225' . $tel;
        } elseif (strlen($tel) === 8) {
            $tel = '225' . $tel;
        }

        return $tel;
    }

    /**
     * Message de relance personnalisé (modèle de l'établissement ou défaut).
     */
    public function messageRelance(Credit $credit, string $type = 'relance'): string
    {
        $client   = $credit->client;
        $institut = Institut::find($credit->institut_id);

        $prochaine = $credit->echeances()
            ->whereIn('statut', ['en_attente', 'retard'])
            ->orderBy('date_prevue')
            ->first();

        $montant = $prochaine ? max(0, (int) $prochaine->montant - (int) $prochaine->montant_paye) : (int) $credit->reste_a_payer;

        $defaut = match ($type) {
            'rappel' => "Bonjour {client},\n\nPetit rappel : votre échéance de {montant} FCFA arrive à échéance le {date}.\nReste à payer : {reste} FCFA.\n\nDétail : {lien}\n\n{institut}",
            'fiche'  => "Bonjour {client},\n\nVoici le détail de votre crédit en cours.\nReste à payer : {reste} FCFA.\nProchaine échéance : {date} ({montant} FCFA).\n\nDétail : {lien}\n\nMerci de votre confiance,\n{institut}",
            default  => "Bonjour {client},\n\nSauf erreur de notre part, votre échéance de {montant} FCFA du {date} n'a pas encore été réglée.\nReste à payer : {reste} FCFA.\n\nDétail : {lien}\n\n{institut}",
        };

        $modele = trim((string) ($institut?->credit_message_relance ?? '')) ?: $defaut;

        return strtr($modele, [
            '{client}'   => $client?->nom_complet ?: 'cher client',
            '{prenom}'   => $client?->prenom ?: '',
            '{montant}'  => number_format($montant, 0, ',', ' '),
            '{reste}'    => number_format((int) $credit->reste_a_payer, 0, ',', ' '),
            '{date}'     => $prochaine?->date_prevue?->format('d/m/Y') ?? '—',
            '{lien}'     => $this->urlSuivi($credit),
            '{institut}' => $institut?->nom ?? config('app.name'),
        ]);
    }

    /** Lien wa.me prêt à envoyer. */
    public function lienWhatsApp(Credit $credit, string $type = 'relance'): ?string
    {
        $tel = $this->numeroWhatsApp($credit->client?->telephone);

        if (! $tel) {
            return null;
        }

        return 'https://wa.me/' . $tel . '?text=' . rawurlencode($this->messageRelance($credit, $type));
    }

    // ─────────────────────────────────────────────────────────────────────
    // Réaménagement
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Rééchelonne les échéances **restantes** (non soldées) d'un crédit.
     * Les échéances déjà payées ne sont pas touchées.
     */
    public function reechelonner(Credit $credit, int $nbEcheances, string $frequence, ?Carbon $dateDebut = null): Credit
    {
        return DB::transaction(function () use ($credit, $nbEcheances, $frequence, $dateDebut) {
            $restant = (int) $credit->reste_a_payer;

            // On supprime uniquement les échéances non entamées
            $credit->echeances()
                ->where('statut', '!=', 'payee')
                ->where('montant_paye', '<=', 0)
                ->delete();

            $date = ($dateDebut ?? Carbon::today())->copy();
            $parEcheance = $nbEcheances > 0 ? (int) floor($restant / $nbEcheances) : $restant;
            $cumul = 0;

            $dernierNumero = (int) $credit->echeances()->max('numero');

            for ($i = 1; $i <= max(1, $nbEcheances); $i++) {
                $date = $frequence === 'hebdomadaire' ? $date->copy()->addWeek() : $date->copy()->addMonth();

                $montant = ($i === $nbEcheances)
                    ? max(0, $restant - $cumul)
                    : $parEcheance;

                $cumul += $montant;

                Echeance::create([
                    'credit_id'   => $credit->id,
                    'institut_id' => $credit->institut_id,
                    'numero'      => $dernierNumero + $i,
                    'date_prevue' => $date->copy(),
                    'montant'     => $montant,
                    'statut'      => 'en_attente',
                ]);
            }

            $credit->update([
                'statut'          => 'en_cours',
                'nb_echeances'    => $credit->echeances()->count(),
                'frequence'       => $frequence,
                'date_fin_prevue' => $date->copy(),
            ]);

            if ($credit->vente) {
                $credit->vente->update(['credit_statut' => 'en_cours']);
            }

            return $credit->refresh();
        });
    }

    /** Report d'une échéance à une nouvelle date. */
    public function reporterEcheance(Echeance $echeance, Carbon $nouvelleDate): Echeance
    {
        $echeance->update([
            'date_prevue'   => $nouvelleDate->toDateString(),
            'statut'        => $echeance->statut === 'retard' ? 'en_attente' : $echeance->statut,
            'date_paiement' => null,
        ]);

        $credit = $echeance->credit;

        if ($credit && $credit->statut === 'retard') {
            $encoreEnRetard = $credit->echeances()
                ->where('statut', 'retard')
                ->whereDate('date_prevue', '<', now()->toDateString())
                ->exists();

            if (! $encoreEnRetard) {
                $credit->update(['statut' => 'en_cours']);
                $credit->vente?->update(['credit_statut' => 'en_cours']);
            }
        }

        return $echeance->refresh();
    }

    /** Reçu texte d'un paiement (partage WhatsApp). */
    public function recuPaiement(PaiementCredit $paiement): string
    {
        $credit = $paiement->credit;

        return "Reçu de paiement\n"
            . "Client : " . ($credit?->client?->nom_complet ?? '—') . "\n"
            . "Montant : " . number_format((int) $paiement->montant, 0, ',', ' ') . " FCFA\n"
            . "Date : " . $paiement->created_at?->format('d/m/Y H:i') . "\n"
            . "Reste à payer : " . number_format((int) ($credit?->reste_a_payer ?? 0), 0, ',', ' ') . " FCFA\n"
            . ($credit ? "Détail : " . $this->urlSuivi($credit) : '');
    }
}
