<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;

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
}
