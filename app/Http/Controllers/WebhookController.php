<?php

namespace App\Http\Controllers;

use App\Models\Abonnement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function cinetpay(Request $request)
    {
        // Vérification de la signature (à implémenter avec la clé secrète CinetPay)
        Log::info('CinetPay webhook reçu', $request->all());

        $transactionId = $request->input('cpm_trans_id');
        $statut = $request->input('cpm_result'); // '00' = succès

        if (!$transactionId) {
            return response()->json(['status' => 'error', 'message' => 'Transaction ID manquant'], 400);
        }

        $abonnement = Abonnement::where('reference_cinetpay', $transactionId)->first();

        if (!$abonnement) {
            return response()->json(['status' => 'error', 'message' => 'Abonnement non trouvé'], 404);
        }

        if ($statut === '00') {
            // Paiement réussi
            $abonnement->update([
                'statut' => 'actif',
                'debut_le' => now()->toDateString(),
                'expire_le' => now()->addDays($abonnement->plan->duree_jours)->toDateString(),
                'metadata' => $request->all(),
            ]);

            Log::info("Abonnement {$abonnement->id} activé via CinetPay");
        } else {
            $abonnement->update([
                'statut' => 'annule',
                'metadata' => $request->all(),
            ]);
        }

        return response()->json(['status' => 'ok']);
    }
}
