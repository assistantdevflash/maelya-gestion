<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;

class AdminPushDebugController extends Controller
{
    public function index()
    {
        return view('admin.push-debug', [
            'vapidPublic'      => config('app.vapid_public_key'),
            'vapidPrivate'     => config('app.vapid_private_key') ? '***' : null,
            'webpushInstalled' => class_exists(\Minishlink\WebPush\WebPush::class),
            'subCount'         => PushSubscription::where('user_id', auth()->id())->count(),
            'allSubs'          => PushSubscription::with('user')->latest()->limit(50)->get(),
        ]);
    }

    public function sendTest(Request $request)
    {
        try {
            app(PushNotificationService::class)->sendToUser(
                auth()->user(),
                '🔔 Test Maëlya Gestion',
                'Si vous voyez cette notification, le système push fonctionne !',
                '/admin/push-debug'
            );
            return response()->json(['ok' => true, 'message' => 'Notification envoyée à vos abonnements.']);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
