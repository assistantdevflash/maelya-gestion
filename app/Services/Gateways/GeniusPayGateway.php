<?php

namespace App\Services\Gateways;

use App\Mail\NouveauPaiementRecu;
use App\Models\Abonnement;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\PushNotificationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class GeniusPayGateway implements PaymentGatewayInterface
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.geniuspay.base_url', 'https://geniuspay.ci/api/v1/merchant');
    }

    public function initiate(PaymentTransaction $transaction, PaymentMethod $method): array
    {
        $response = Http::timeout(30)->withHeaders([
            'X-API-Key'    => config('services.geniuspay.api_key'),
            'X-API-Secret' => config('services.geniuspay.api_secret'),
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/payments", [
            'amount'      => (int) $transaction->amount,
            'currency'    => $transaction->currency,
            'description' => $this->buildDescription($transaction),
            'customer'    => $this->buildCustomer($transaction),
            'success_url' => route('payment.success', ['ref' => $transaction->reference]),
            'error_url'   => route('payment.error',   ['ref' => $transaction->reference]),
            'metadata'    => [
                'maelya_ref'   => $transaction->reference,
                'user_id'      => $transaction->user_id,
                'institut_id'  => $transaction->institut_id,
                'type'         => $transaction->type,
            ],
        ]);

        if (!$response->successful()) {
            Log::error('[GeniusPay] initiate failed', [
                'ref'      => $transaction->reference,
                'status'   => $response->status(),
                'response' => $response->json(),
            ]);
            throw new \RuntimeException('Échec de l\'initialisation du paiement GeniusPay : ' . ($response->json('error.message') ?? 'Erreur inconnue'));
        }

        $data = $response->json('data');

        $transaction->update([
            'gateway_reference' => $data['reference'],
            'checkout_url'      => $data['checkout_url'] ?? $data['payment_url'],
            'gateway_status'    => $data['status'],
            'gateway_response'  => json_encode($data),
            'expires_at'        => now()->addHours(24),
        ]);

        Log::info('[GeniusPay] payment initiated', [
            'maelya_ref'  => $transaction->reference,
            'gateway_ref' => $data['reference'],
        ]);

        return [
            'success'      => true,
            'checkout_url' => $data['checkout_url'] ?? $data['payment_url'],
            'reference'    => $data['reference'],
        ];
    }

    public function verify(PaymentTransaction $transaction): bool
    {
        if (!$transaction->gateway_reference) {
            return false;
        }

        $response = Http::timeout(15)->withHeaders([
            'X-API-Key'    => config('services.geniuspay.api_key'),
            'X-API-Secret' => config('services.geniuspay.api_secret'),
        ])->get("{$this->baseUrl}/payments/{$transaction->gateway_reference}");

        if (!$response->successful()) {
            Log::warning('[GeniusPay] verify failed', [
                'ref' => $transaction->gateway_reference,
            ]);
            return false;
        }

        $data = $response->json('data');

        $transaction->update([
            'gateway_status'   => $data['status'],
            'gateway_response' => json_encode($data),
        ]);

        if ($data['status'] === 'completed') {
            $this->activateFromGatewayData($transaction, $data);
            return true;
        }

        return false;
    }

    public function handleWebhook(array $payload): void
    {
        $event = $payload['event'] ?? null;
        $data  = $payload['data'] ?? [];

        $maelyaRef = $data['metadata']['maelya_ref'] ?? null;
        if (!$maelyaRef) {
            Log::warning('[GeniusPay] webhook sans référence Maëlya', compact('payload'));
            return;
        }

        $transaction = PaymentTransaction::where('reference', $maelyaRef)->first();
        if (!$transaction) {
            Log::warning('[GeniusPay] transaction introuvable', ['ref' => $maelyaRef]);
            return;
        }

        match ($event) {
            'payment.success', 'payment.completed' => $this->activateFromGatewayData($transaction, $data),
            'payment.failed'    => $this->markFailed($transaction, $data),
            'payment.cancelled' => $this->markCancelled($transaction, $data),
            'payment.expired'   => $this->markExpired($transaction, $data),
            'payment.refunded'  => $this->markRefunded($transaction, $data),
            default             => Log::info('[GeniusPay] webhook ignoré', ['event' => $event]),
        };
    }

    /**
     * Marque une transaction comme échouée et nettoie l'abonnement en attente associé.
     */
    private function markFailed(PaymentTransaction $transaction, array $data): void
    {
        $transaction->update([
            'status'           => 'failed',
            'gateway_status'   => 'failed',
            'gateway_response' => json_encode($data),
        ]);
        $this->annulerAbonnementEnAttente($transaction);
        Log::info('[GeniusPay] paiement échoué', ['ref' => $transaction->reference]);
    }

    private function markCancelled(PaymentTransaction $transaction, array $data): void
    {
        $transaction->update([
            'status'           => 'cancelled',
            'gateway_status'   => 'cancelled',
            'gateway_response' => json_encode($data),
        ]);
        $this->annulerAbonnementEnAttente($transaction);
        Log::info('[GeniusPay] paiement annulé', ['ref' => $transaction->reference]);
    }

    private function markExpired(PaymentTransaction $transaction, array $data): void
    {
        $transaction->update([
            'status'           => 'expired',
            'gateway_status'   => 'expired',
            'gateway_response' => json_encode($data),
        ]);
        $this->annulerAbonnementEnAttente($transaction);
        Log::info('[GeniusPay] paiement expiré', ['ref' => $transaction->reference]);
    }

    /**
     * Supprime l'abonnement "en_attente" lié à un paiement qui n'aboutit pas,
     * pour éviter les demandes orphelines en attente de validation.
     */
    private function annulerAbonnementEnAttente(PaymentTransaction $transaction): void
    {
        if (!$transaction->abonnement_id) {
            return;
        }

        $abonnement = Abonnement::find($transaction->abonnement_id);
        if ($abonnement && $abonnement->statut === 'en_attente') {
            $abonnement->update([
                'statut'      => 'rejete',
                'notes_admin' => ($abonnement->notes_admin ? $abonnement->notes_admin . "\n" : '')
                    . 'Paiement ' . $transaction->status . ' — transaction ' . $transaction->reference,
            ]);
            Log::info('[GeniusPay] abonnement annulé suite échec paiement', [
                'abonnement_id' => $abonnement->id,
                'transaction'   => $transaction->reference,
            ]);
        }
    }

    private function markRefunded(PaymentTransaction $transaction, array $data): void
    {
        if ($transaction->status !== 'completed') {
            return;
        }

        $transaction->update([
            'status'           => 'refunded',
            'gateway_status'   => 'refunded',
            'refunded_at'      => now(),
            'refund_reference' => $data['refund_reference'] ?? ($data['reference'] ?? null),
            'refunded_amount'  => (int) ($data['amount'] ?? $transaction->amount),
            'gateway_response' => json_encode($data),
        ]);

        // Désactiver le service lié (abonnement/boutique)
        $this->desactiverService($transaction);

        Log::info('[GeniusPay] remboursement reçu', [
            'ref'  => $transaction->reference,
            'montant' => $transaction->refunded_amount,
        ]);
    }

    /**
     * Rembourse une transaction via l'API GeniusPay.
     */
    public function refund(PaymentTransaction $transaction, ?string $reason = null): array
    {
        if (!$transaction->isCompleted()) {
            return ['success' => false, 'message' => 'Seules les transactions complétées peuvent être remboursées.'];
        }

        if ($transaction->status === 'refunded') {
            return ['success' => false, 'message' => 'Cette transaction est déjà remboursée.'];
        }

        if (!$transaction->gateway_reference) {
            return ['success' => false, 'message' => 'Référence gateway manquante pour le remboursement.'];
        }

        $response = Http::timeout(30)->withHeaders([
            'X-API-Key'    => config('services.geniuspay.api_key'),
            'X-API-Secret' => config('services.geniuspay.api_secret'),
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/payments/{$transaction->gateway_reference}/refund", array_filter([
            'amount' => $transaction->amount,
            'reason' => $reason,
        ]));

        if (!$response->successful()) {
            Log::error('[GeniusPay] refund failed', [
                'ref'      => $transaction->reference,
                'status'   => $response->status(),
                'response' => $response->json(),
            ]);
            return [
                'success' => false,
                'message' => 'Erreur GeniusPay : ' . ($response->json('error.message') ?? 'Erreur inconnue'),
            ];
        }

        $data = $response->json('data');

        $transaction->update([
            'status'           => 'refunded',
            'gateway_status'   => 'refunded',
            'refunded_at'      => now(),
            'refund_reference' => $data['reference'] ?? ($data['refund_reference'] ?? null),
            'refunded_amount'  => (int) ($data['amount'] ?? $transaction->amount),
            'gateway_response' => json_encode($data),
        ]);

        $this->desactiverService($transaction);

        Log::info('[GeniusPay] remboursement effectué', [
            'ref'    => $transaction->reference,
            'amount' => $transaction->refunded_amount,
        ]);

        return ['success' => true, 'refund_reference' => $transaction->refund_reference];
    }

    /**
     * Désactive le service (abonnement ou boutique) associé à un remboursement.
     */
    private function desactiverService(PaymentTransaction $transaction): void
    {
        $abonnement = $transaction->abonnement_id ? Abonnement::find($transaction->abonnement_id) : null;
        if (!$abonnement) {
            return;
        }

        if (in_array($transaction->type, ['boutique_activation', 'boutique_renouvellement'])) {
            // Désactiver l'option boutique
            if ($abonnement->hasBoutique()) {
                $abonnement->setBoutique(false, 3900);
                $abonnement->save();
                Cache::forget("user_{$abonnement->user_id}_boutique_access");
            }
        } elseif (in_array($transaction->type, ['abonnement', 'renouvellement', 'upgrade'])) {
            // Expirer l'abonnement
            if ($abonnement->statut === 'actif') {
                $abonnement->update([
                    'statut'      => 'expire',
                    'notes_admin' => ($abonnement->notes_admin ? $abonnement->notes_admin . "\n" : '')
                        . 'Remboursement transaction ' . $transaction->reference . ' le ' . now()->format('d/m/Y'),
                ]);
            }
        }
    }

    private function activateFromGatewayData(PaymentTransaction $transaction, array $data): void
    {
        // Idempotence : ne rien faire si déjà complété
        if ($transaction->status === 'completed') {
            return;
        }

        $transaction->update([
            'status'           => 'completed',
            'gateway_status'   => 'completed',
            'paid_at'          => now(),
            'fees'             => (int) ($data['fees'] ?? 0),
            'net_amount'       => (int) ($data['net_amount'] ?? $transaction->amount),
            'gateway_response' => json_encode($data),
        ]);

        Log::info('[GeniusPay] paiement complété', [
            'ref'  => $transaction->reference,
            'type' => $transaction->type,
        ]);

        $this->activateService($transaction);
    }

    private function activateService(PaymentTransaction $transaction): void
    {
        if (in_array($transaction->type, ['abonnement', 'renouvellement', 'upgrade'])) {
            $this->activateAbonnement($transaction);
        } elseif (in_array($transaction->type, ['boutique_activation', 'boutique_renouvellement'])) {
            $this->activateBoutique($transaction);
        }
    }

    private function activateAbonnement(PaymentTransaction $transaction): void
    {
        $abonnement = Abonnement::find($transaction->abonnement_id);
        if (!$abonnement) {
            Log::error('[GeniusPay] abonnement_id introuvable pour activation', ['tx' => $transaction->reference]);
            return;
        }

        if ($abonnement->statut === 'actif') {
            return; // Déjà activé
        }

        $plan  = $abonnement->plan;
        $jours = $plan->joursPourPeriode($abonnement->periode);

        // Expirer les abonnements actifs précédents
        Abonnement::where('user_id', $abonnement->user_id)
            ->where('id', '!=', $abonnement->id)
            ->where('statut', 'actif')
            ->update(['statut' => 'expire']);

        $debut = now();
        $abonnement->update([
            'statut'    => 'actif',
            'debut_le'  => $debut->toDateString(),
            'expire_le' => $debut->copy()->addDays($jours)->toDateString(),
            'metadata'  => array_merge($abonnement->metadata ?? [], [
                'payment_method' => 'geniuspay',
                'geniuspay_ref'  => $transaction->gateway_reference,
            ]),
        ]);

        // Lier la transaction à l'abonnement
        $transaction->update(['abonnement_id' => $abonnement->id]);

        // Invalider les caches
        Cache::forget("user_{$abonnement->user_id}_boutique_access");

        // Notification in-app
        $user = $abonnement->user;
        if ($user) {
            app(\App\Services\NotificationService::class)::notifyUser(
                $user,
                'abonnement_valide',
                '✅ Abonnement activé automatiquement',
                "Votre abonnement {$plan->nom} est actif jusqu'au {$abonnement->expire_le->format('d/m/Y')}.",
                '/abonnement/historique'
            );
            try {
                app(\App\Services\PushNotificationService::class)->sendToUser(
                    $user,
                    '✅ Abonnement activé !',
                    "Votre abonnement {$plan->nom} est maintenant actif.",
                    '/abonnement/historique'
                );
            } catch (\Throwable $e) {
                Log::warning('[Push] ' . $e->getMessage());
            }
        }

        // Notifier les super admins (email + in-app + push)
        $this->notifierAdminsPaiementRecu($transaction);

        Log::info('[GeniusPay] abonnement activé', [
            'abonnement_id' => $abonnement->id,
            'user_id'       => $abonnement->user_id,
            'plan'          => $plan->nom,
            'expire_le'     => $abonnement->expire_le,
        ]);
    }

    private function activateBoutique(PaymentTransaction $transaction): void
    {
        $abonnement = Abonnement::find($transaction->abonnement_id);
        if (!$abonnement) {
            Log::error('[GeniusPay] abonnement source introuvable pour boutique', ['tx' => $transaction->reference]);
            return;
        }

        if ($abonnement->hasBoutique()) {
            return; // Déjà activée
        }

        $abonnement->setBoutique(true, 3900);
        $abonnement->save();

        Cache::forget("user_{$abonnement->user_id}_boutique_access");

        $user = $abonnement->user;
        if ($user) {
            app(\App\Services\NotificationService::class)::notifyUser(
                $user,
                'option_boutique_activee',
                '🛍️ Boutique en ligne activée !',
                'Votre boutique est maintenant active. Vos clients peuvent commander en ligne.',
                '/dashboard/boutique/config'
            );
            try {
                app(\App\Services\PushNotificationService::class)->sendToUser(
                    $user,
                    '🛍️ Boutique activée !',
                    'Votre boutique en ligne est maintenant active.',
                    '/dashboard/boutique/config'
                );
            } catch (\Throwable $e) {
                Log::warning('[Push] ' . $e->getMessage());
            }
        }

        // Notifier les super admins (email + in-app + push)
        $this->notifierAdminsPaiementRecu($transaction);

        Log::info('[GeniusPay] boutique activée', [
            'abonnement_id' => $abonnement->id,
            'user_id'       => $abonnement->user_id,
        ]);
    }

    private function buildDescription(PaymentTransaction $transaction): string
    {
        return match ($transaction->type) {
            'abonnement'            => 'Abonnement Maëlya — ' . ($transaction->metadata['plan_nom'] ?? ''),
            'renouvellement'        => 'Renouvellement abonnement Maëlya — ' . ($transaction->metadata['plan_nom'] ?? ''),
            'boutique_activation'   => 'Activation boutique en ligne Maëlya',
            'boutique_renouvellement' => 'Renouvellement boutique en ligne Maëlya',
            'upgrade'               => 'Mise à niveau abonnement Maëlya',
            default                 => 'Paiement Maëlya Gestion',
        };
    }

    private function buildCustomer(PaymentTransaction $transaction): array
    {
        $user = $transaction->user;
        return array_filter([
            'name'  => $user?->name,
            'email' => $user?->email,
            'phone' => $user?->telephone ?? null,
        ]);
    }

    /**
     * Notifie tous les super admins qu'un paiement GeniusPay a été reçu
     * (email + notification in-app + notification push).
     */
    public function notifierAdminsPaiementRecu(PaymentTransaction $transaction): void
    {
        $user    = $transaction->user;
        $type    = $transaction->type;
        $libelle = NouveauPaiementRecu::typeLabel($type);
        $montant = number_format($transaction->amount, 0, ',', ' ');

        // In-app
        NotificationService::notifyAdmins(
            'paiement_recu',
            '💳 ' . $libelle,
            ($user?->nom_complet ?? 'Un client') . ' a payé ' . $montant . ' FCFA (' . $transaction->reference . ').',
            '/admin/payment-transactions'
        );

        // Email
        try {
            User::where('role', 'super_admin')->each(function (User $admin) use ($transaction) {
                Mail::to($admin->email)->send(new NouveauPaiementRecu($transaction));
            });
        } catch (\Throwable $e) {
            Log::warning('[GeniusPay] email admin paiement échoué : ' . $e->getMessage());
        }

        // Push
        try {
            app(PushNotificationService::class)->sendToAdmins(
                '💳 ' . $libelle,
                ($user?->nom_complet ?? 'Un client') . ' a payé ' . $montant . ' FCFA.',
                '/admin/payment-transactions'
            );
        } catch (\Throwable $e) {
            Log::warning('[Push] ' . $e->getMessage());
        }
    }
}
