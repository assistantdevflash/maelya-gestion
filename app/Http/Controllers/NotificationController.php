<?php

namespace App\Http\Controllers;

use App\Models\Notif;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Afficher toutes les notifications de l'utilisateur connecté.
     */
    public function index(): View
    {
        $notifs = Notif::where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->paginate(30);

        return view('notifications.index', compact('notifs'));
    }

    /**
     * Marquer toutes les notifications non lues de l'utilisateur connecté comme lues.
     */
    public function toutLire(): JsonResponse
    {
        Notif::where('user_id', Auth::id())->where('lu', false)->update(['lu' => true]);

        return response()->json(['ok' => true]);
    }
}
