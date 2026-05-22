<?php

namespace App\Http\Controllers;

use App\Models\Notif;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Marquer toutes les notifications non lues de l'utilisateur connecté comme lues.
     */
    public function toutLire(): JsonResponse
    {
        Notif::where('user_id', Auth::id())->where('lu', false)->update(['lu' => true]);

        return response()->json(['ok' => true]);
    }
}
