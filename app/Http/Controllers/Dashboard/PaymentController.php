<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    /** Page de confirmation après paiement GeniusPay réussi */
    public function success(Request $request)
    {
        $transaction = PaymentTransaction::where('reference', $request->ref)->firstOrFail();
        
        // Connecter automatiquement l'utilisateur si déconnecté
        if (!Auth::check()) {
            Auth::login($transaction->user);
        }

        // Si le webhook n'est pas encore arrivé, on tente une vérification directe
        if ($transaction->isPending()) {
            try {
                app(\App\Services\PaymentGatewayManager::class)->verify($transaction);
                $transaction->refresh();
            } catch (\Throwable $e) {
                // Silencieux — le webhook prendra le relais
            }
        }

        return view('dashboard.payment.success', compact('transaction'));
    }

    /** Page d'erreur après échec de paiement */
    public function error(Request $request)
    {
        $transaction = PaymentTransaction::where('reference', $request->ref)->firstOrFail();
        
        // Connecter automatiquement l'utilisateur si déconnecté
        if (!Auth::check()) {
            Auth::login($transaction->user);
        }

        return view('dashboard.payment.error', compact('transaction'));
    }

    /** Page d'instructions pour le transfert bancaire */
    public function bankTransfer(Request $request)
    {
        $transaction = PaymentTransaction::where('reference', $request->ref)->firstOrFail();
        
        // Connecter automatiquement l'utilisateur si déconnecté
        if (!Auth::check()) {
            Auth::login($transaction->user);
        }

        return view('dashboard.payment.bank-transfer', compact('transaction'));
    }

    /** Page de paiement en attente (webhook pas encore reçu) */
    public function pending(Request $request)
    {
        $transaction = PaymentTransaction::where('reference', $request->ref)->firstOrFail();
        
        // Connecter automatiquement l'utilisateur si déconnecté
        if (!Auth::check()) {
            Auth::login($transaction->user);
        }

        return view('dashboard.payment.pending', compact('transaction'));
    }
}
