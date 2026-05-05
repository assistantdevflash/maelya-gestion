<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'endpoint'   => ['required', 'string'],
            'public_key' => ['required', 'string'],
            'auth_token' => ['required', 'string'],
        ]);

        PushSubscription::updateOrCreate(
            [
                'user_id'  => auth()->id(),
                'endpoint' => $request->endpoint,
            ],
            [
                'public_key' => $request->public_key,
                'auth_token' => $request->auth_token,
                'user_agent' => $request->userAgent(),
            ]
        );

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'endpoint' => ['required', 'string'],
        ]);

        PushSubscription::where('user_id', auth()->id())
            ->where('endpoint', $request->endpoint)
            ->delete();

        return response()->json(['ok' => true]);
    }
}
