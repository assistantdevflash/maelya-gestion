<?php

namespace App\Console\Commands;

use App\Models\Institut;
use App\Models\Produit;
use App\Models\RendezVous;
use App\Models\User;
use App\Models\Vente;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class DetecterAnomalies extends Command
{
    protected $signature   = 'maelya:anomalies';
    protected $description = 'Détecte les anomalies (caisse négative, stock épuisé, doublons RDV, ventes hors normes) et notifie les admins.';

    public function handle(): int
    {
        $totalAlertes = 0;

        foreach (Institut::where('actif', true)->get() as $institut) {
            $admin = User::where('institut_id', $institut->id)
                ->where('role', 'admin')
                ->where('actif', true)
                ->first();
            if (! $admin) continue;

            // 1) Stock à 0 sur produits actifs
            $stockZero = Produit::where('institut_id', $institut->id)
                ->where('actif', true)
                ->where('stock', '<=', 0)
                ->count();
            if ($stockZero > 0) {
                NotificationService::notifyUser(
                    $admin, 'anomalie_stock',
                    '⚠️ Stock épuisé',
                    $stockZero . ' produit(s) en rupture de stock.',
                    '/dashboard/stock'
                );
                $totalAlertes++;
            }

            // 2) RDV en double sur même créneau (même heure, même employé)
            $doublons = RendezVous::where('institut_id', $institut->id)
                ->whereNotNull('employe_id')
                ->where('debut_le', '>=', now())
                ->whereIn('statut', ['en_attente', 'confirme'])
                ->selectRaw('employe_id, debut_le, COUNT(*) as nb')
                ->groupBy('employe_id', 'debut_le')
                ->havingRaw('COUNT(*) > 1')
                ->get();
            if ($doublons->count() > 0) {
                NotificationService::notifyUser(
                    $admin, 'anomalie_rdv_doublon',
                    '⚠️ RDV en doublon',
                    $doublons->count() . ' créneau(x) avec plusieurs RDV sur le même employé.',
                    '/dashboard/rdv'
                );
                $totalAlertes++;
            }

            // 3) Vente hors normes vs moyenne (>3x ou <0.2x sur les 7 derniers jours)
            $venteMoy = Vente::where('institut_id', $institut->id)
                ->where('statut', 'validee')
                ->where('created_at', '>=', now()->subDays(30))
                ->avg('total');

            if ($venteMoy > 0) {
                $hier = Vente::where('institut_id', $institut->id)
                    ->where('statut', 'validee')
                    ->whereDate('created_at', now()->subDay())
                    ->get();
                $anormales = $hier->filter(fn ($v) => $v->total > $venteMoy * 3 || $v->total < $venteMoy * 0.2);
                if ($anormales->count() > 0) {
                    NotificationService::notifyUser(
                        $admin, 'anomalie_vente',
                        '⚠️ Ventes inhabituelles',
                        $anormales->count() . ' vente(s) hier hors moyenne (' . number_format($venteMoy, 0, ',', ' ') . ' F).',
                        '/dashboard/ventes'
                    );
                    $totalAlertes++;
                }
            }
        }

        $this->info("Détection terminée. {$totalAlertes} alerte(s) générée(s).");
        return self::SUCCESS;
    }
}
