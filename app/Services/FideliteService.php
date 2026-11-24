<?php

namespace App\Services;

use App\Models\Client;
use App\Models\CodeReduction;
use App\Models\HistoriquePoints;
use App\Models\ProgrammeFidelite;
use App\Models\Vente;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Règles du programme de fidélité, réunies en un seul endroit.
 *
 * L'attribution des points existait uniquement dans le tunnel de vente
 * comptant : une vente à crédit n'en rapportait aucun. Toutes les entrées
 * (caisse, crédit, annulation, conversion, ajustement) passent désormais par
 * ce service, ce qui garantit un barème et un historique identiques.
 */
class FideliteService
{
    /** Préfixe des codes générés par une conversion. */
    public const PREFIXE_CODE = 'FID-';

    public function programmeActif(string $institutId): ?ProgrammeFidelite
    {
        return ProgrammeFidelite::where('institut_id', $institutId)
            ->where('actif', true)
            ->first();
    }

    /**
     * Crédite les points d'une vente, quel que soit son mode de paiement.
     * Retourne le nombre de points attribués (0 si le programme est inactif).
     */
    public function attribuerPointsPourVente(Vente $vente, ?string $userId = null): int
    {
        if (! $vente->client_id) {
            return 0;
        }

        $programme = $this->programmeActif($vente->institut_id);

        if (! $programme) {
            return 0;
        }

        $points = $programme->calculerPoints((int) $vente->total);

        if ($points <= 0) {
            return 0;
        }

        $client = Client::withoutGlobalScopes()->find($vente->client_id);

        if (! $client) {
            return 0;
        }

        $client->increment('points_fidelite', $points);

        HistoriquePoints::create([
            'institut_id' => $vente->institut_id,
            'client_id'   => $client->id,
            'vente_id'    => $vente->id,
            'user_id'     => $userId,
            'points'      => $points,
            'type'        => 'gain',
            'description' => "+{$points} pts (vente #{$vente->numero})",
        ]);

        return $points;
    }

    /**
     * Reprend les points d'une vente annulée, sans jamais descendre sous zéro.
     */
    public function retirerPointsPourAnnulation(Vente $vente, ?string $userId = null): int
    {
        if (! $vente->client_id) {
            return 0;
        }

        $gagnes = (int) HistoriquePoints::where('vente_id', $vente->id)
            ->where('type', 'gain')
            ->sum('points');

        if ($gagnes <= 0) {
            return 0;
        }

        $client = Client::withoutGlobalScopes()->find($vente->client_id);

        if (! $client) {
            return 0;
        }

        $aRetirer = min($gagnes, max(0, (int) $client->points_fidelite));

        if ($aRetirer <= 0) {
            return 0;
        }

        $client->decrement('points_fidelite', $aRetirer);

        HistoriquePoints::create([
            'institut_id' => $vente->institut_id,
            'client_id'   => $client->id,
            'vente_id'    => $vente->id,
            'user_id'     => $userId,
            'points'      => -$aRetirer,
            'type'        => 'perte',
            'description' => "Annulation vente #{$vente->numero}",
        ]);

        return $aRetirer;
    }

    /**
     * Convertit le seuil de points en code de réduction personnel.
     *
     * Le client est verrouillé le temps de l'opération : deux clics
     * simultanés ne peuvent plus débiter deux fois.
     */
    public function convertir(Client $client, ProgrammeFidelite $programme, ?string $userId = null): CodeReduction
    {
        return DB::transaction(function () use ($client, $programme, $userId) {
            $verrouille = Client::withoutGlobalScopes()->lockForUpdate()->findOrFail($client->id);

            if ((int) $verrouille->points_fidelite < $programme->seuil_recompense) {
                throw ValidationException::withMessages([
                    'points' => 'Points insuffisants pour cette récompense.',
                ]);
            }

            $verrouille->decrement('points_fidelite', $programme->seuil_recompense);

            $code = $this->creerCodeUnique($verrouille, $programme);

            HistoriquePoints::create([
                'institut_id'       => $verrouille->institut_id,
                'client_id'         => $verrouille->id,
                'user_id'           => $userId,
                'code_reduction_id' => $code->id,
                'points'            => -$programme->seuil_recompense,
                'type'              => 'recompense',
                'description'       => 'Conversion en code ' . $code->code,
            ]);

            return $code;
        });
    }

    /**
     * Ajuste le solde de points et journalise **le delta réellement appliqué**.
     *
     * Auparavant, retirer 10 points à un client qui en avait 3 enregistrait
     * −10 dans l'historique alors que le solde tombait à 0 : la somme des
     * mouvements ne correspondait plus jamais au solde.
     *
     * @return int Le nombre de points réellement appliqués (peut être négatif).
     */
    public function ajuster(Client $client, int $points, string $description, ?string $userId = null): int
    {
        return DB::transaction(function () use ($client, $points, $description, $userId) {
            $verrouille = Client::withoutGlobalScopes()->lockForUpdate()->findOrFail($client->id);

            $avant    = (int) $verrouille->points_fidelite;
            $apres    = max(0, $avant + $points);
            $applique = $apres - $avant;

            if ($applique === 0) {
                throw ValidationException::withMessages([
                    'points' => $avant === 0
                        ? 'Ce client n\'a aucun point : cet ajustement ne changerait rien.'
                        : 'Cet ajustement ne change pas le solde du client.',
                ]);
            }

            $verrouille->update(['points_fidelite' => $apres]);

            HistoriquePoints::create([
                'institut_id' => $verrouille->institut_id,
                'client_id'   => $verrouille->id,
                'user_id'     => $userId,
                'points'      => $applique,
                'type'        => 'ajustement',
                'description' => $description,
            ]);

            return $applique;
        });
    }

    /**
     * Génère un code de récompense dont le code est libre.
     *
     * Le tirage aléatoire peut tomber sur un code déjà attribué : on réessaie
     * plutôt que de laisser remonter une erreur SQL.
     */
    private function creerCodeUnique(Client $client, ProgrammeFidelite $programme): CodeReduction
    {
        for ($tentative = 1; $tentative <= 10; $tentative++) {
            $code = self::PREFIXE_CODE . strtoupper(Str::random(6));

            $existe = CodeReduction::withoutGlobalScopes()
                ->where('institut_id', $client->institut_id)
                ->where('code', $code)
                ->exists();

            if ($existe) {
                continue;
            }

            return CodeReduction::create([
                'institut_id'        => $client->institut_id,
                'client_id'          => $client->id,
                'code'               => $code,
                'description'        => 'Récompense fidélité pour ' . $client->nom_complet,
                'type'               => $programme->type_recompense,
                'valeur'             => $programme->valeur_recompense,
                'date_debut'         => now(),
                'date_fin'           => now()->addDays(30),
                'limite_utilisation' => 1,
                'actif'              => true,
            ]);
        }

        throw new \RuntimeException('Impossible de générer un code de récompense unique.');
    }
}
