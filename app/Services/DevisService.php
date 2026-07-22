<?php
namespace App\Services;
use App\Models\Devis;
use App\Models\Facture;
use App\Models\FactureItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DevisService
{
    public static function genererNumero(string $institutId): string
    {
        return DB::transaction(function () use ($institutId) {
            $date = now()->format('Ymd');
            $last = Devis::where('institut_id', $institutId)
                ->where('numero', 'like', "DEV-{$date}-%")
                ->orderByRaw('CAST(SUBSTRING_INDEX(numero, \'-\', -1) AS UNSIGNED) DESC')
                ->lockForUpdate()->first();
            $next = $last ? ((int)substr($last->numero, -6)) + 1 : 1;
            return sprintf('DEV-%s-%06d', $date, $next);
        });
    }

    public static function calculerTotaux(array $lignes, array $data): array
    {
        $sousTotal = 0;
        foreach ($lignes as &$ligne) {
            $remise = 0;
            if (($ligne['remise_type'] ?? '') === 'pourcentage') {
                $remise = (int)round($ligne['prix_unitaire'] * (int)($ligne['remise_valeur'] ?? 0) / 100);
            } elseif (($ligne['remise_type'] ?? '') === 'montant_fixe') {
                $remise = (int)($ligne['remise_valeur'] ?? 0);
            }
            $ligne['total_ligne'] = max(0, ($ligne['prix_unitaire'] - $remise)) * (int)$ligne['quantite'];
            $sousTotal += $ligne['total_ligne'];
        }
        unset($ligne);

        $remiseGlobale = 0;
        if (($data['remise_globale_type'] ?? '') === 'pourcentage') {
            $remiseGlobale = (int)round($sousTotal * (int)($data['remise_globale_valeur'] ?? 0) / 100);
        } elseif (($data['remise_globale_type'] ?? '') === 'montant_fixe') {
            $remiseGlobale = (int)($data['remise_globale_valeur'] ?? 0);
        }
        $totalHT = max(0, $sousTotal - $remiseGlobale);
        $tvaTaux = !empty($data['tva_applicable']) ? (float)($data['tva_taux'] ?? 0) : 0;
        $montantTVA = $tvaTaux > 0 ? (int)round($totalHT * $tvaTaux / 100) : 0;
        $totalTTC = $totalHT + $montantTVA;

        return [$sousTotal, $remiseGlobale, $totalHT, $montantTVA, $totalTTC, $lignes];
    }

    public static function transformerEnFacture(Devis $devis): Facture
    {
        return DB::transaction(function () use ($devis) {
            $facture = Facture::create([
                'institut_id' => $devis->institut_id,
                'devis_id' => $devis->id,
                'client_id' => $devis->client_id,
                'user_id' => auth()->id(),
                'numero' => FactureService::genererNumero($devis->institut_id),
                'statut' => 'en_attente',
                'date_emission' => now()->toDateString(),
                'date_echeance' => now()->addDays(30)->toDateString(),
                'client_prenom' => $devis->client_prenom,
                'client_nom' => $devis->client_nom,
                'client_email' => $devis->client_email,
                'client_telephone' => $devis->client_telephone,
                'client_adresse' => $devis->client_adresse,
                'sous_total' => $devis->sous_total,
                'remise_globale_type' => $devis->remise_globale_type,
                'remise_globale_valeur' => $devis->remise_globale_valeur,
                'total_ht' => $devis->total_ht,
                'tva_applicable' => $devis->tva_applicable,
                'tva_taux' => $devis->tva_taux,
                'total_ttc' => $devis->total_ttc,
                'notes' => $devis->notes,
                'conditions' => $devis->conditions,
                'titre' => $devis->titre,
                'token' => Str::random(32),
            ]);
            foreach ($devis->items as $item) {
                FactureItem::create([
                    'facture_id' => $facture->id,
                    'produit_id' => $item->produit_id,
                    'prestation_id' => $item->prestation_id,
                    'designation' => $item->designation,
                    'quantite' => $item->quantite,
                    'prix_unitaire' => $item->prix_unitaire,
                    'remise_type' => $item->remise_type,
                    'remise_valeur' => $item->remise_valeur,
                    'tva_taux' => $item->tva_taux,
                    'total_ligne' => $item->total_ligne,
                    'ordre' => $item->ordre,
                ]);
            }
            $devis->update(['facture_id' => $facture->id, 'statut' => 'accepte']);
            return $facture;
        });
    }
}
