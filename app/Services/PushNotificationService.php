<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    private WebPush $webPush;

    public function __construct()
    {
        $this->webPush = new WebPush([
            'VAPID' => [
                'subject'    => config('app.vapid_subject', 'mailto:contact@maelyagestion.com'),
                'publicKey'  => config('app.vapid_public_key'),
                'privateKey' => config('app.vapid_private_key'),
            ],
        ]);
        $this->webPush->setReuseVAPIDHeaders(true);
    }

    /**
     * Envoyer une notification à un utilisateur spécifique.
     */
    public function sendToUser(User $user, string $titre, string $corps, string $url = '/', string $icon = '/icons/icon-192.png'): void
    {
        $subscriptions = PushSubscription::where('user_id', $user->id)->get();
        $this->dispatch($subscriptions, $titre, $corps, $url, $icon);
    }

    /**
     * Envoyer une notification à tous les super_admins.
     */
    public function sendToAdmins(string $titre, string $corps, string $url = '/admin', string $icon = '/icons/icon-192.png'): void
    {
        $adminIds = User::where('role', 'super_admin')->pluck('id');
        $subscriptions = PushSubscription::whereIn('user_id', $adminIds)->get();
        $this->dispatch($subscriptions, $titre, $corps, $url, $icon);
    }

    /**
     * Envoyer une notification à un ensemble d'utilisateurs.
     */
    public function sendToUsers(iterable $users, string $titre, string $corps, string $url = '/', string $icon = '/icons/icon-192.png'): void
    {
        $userIds = collect($users)->pluck('id');
        $subscriptions = PushSubscription::whereIn('user_id', $userIds)->get();
        $this->dispatch($subscriptions, $titre, $corps, $url, $icon);
    }

    private function dispatch($subscriptions, string $titre, string $corps, string $url, string $icon): void
    {
        if ($subscriptions->isEmpty()) {
            return;
        }

        $payload = json_encode([
            'title' => $titre,
            'body'  => $corps,
            'icon'  => $icon,
            'url'   => $url,
            'badge' => '/icons/icon-72.png',
        ]);

        foreach ($subscriptions as $sub) {
            try {
                $subscription = Subscription::create([
                    'endpoint'        => $sub->endpoint,
                    'keys' => [
                        'p256dh' => $sub->public_key,
                        'auth'   => $sub->auth_token,
                    ],
                ]);
                $this->webPush->queueNotification($subscription, $payload);
            } catch (\Exception $e) {
                Log::warning('[Push] Subscription invalide id=' . $sub->id . ' : ' . $e->getMessage());
            }
        }

        // Envoyer toutes les notifications en batch
        foreach ($this->webPush->flush() as $report) {
            if (!$report->isSuccess()) {
                $endpoint = $report->getRequest()->getUri()->__toString();
                Log::warning('[Push] Échec envoi : ' . $report->getReason());

                // Supprimer les subscriptions expirées/invalides
                if ($report->isSubscriptionExpired()) {
                    PushSubscription::where('endpoint', 'LIKE', '%' . substr($endpoint, -50) . '%')->delete();
                }
            }
        }
    }
}
