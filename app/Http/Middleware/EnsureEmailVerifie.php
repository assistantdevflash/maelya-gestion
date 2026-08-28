<?php

namespace App\Http\Middleware;

use App\Models\Institut;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class EnsureEmailVerifie
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user || in_array($user->role, ['super_admin', 'commercial'])) {
            return $next($request);
        }

        if ($request->routeIs('verification.*', 'logout')) {
            return $next($request);
        }

        // Propriétaire / gérant : vérifier son propre email
        if (in_array($user->role, ['admin', 'gerant'])) {
            if (!$user->email_verified_at && $user->created_at->diffInDays(now()) >= 3) {
                return redirect()->route('verification.email')
                    ->with('info', 'Vous devez vérifier votre adresse email pour continuer.');
            }
            return $next($request);
        }

        // Employé : vérifier si le propriétaire de l'établissement a vérifié son email
        if ($user->role === 'employe') {
            $institutId = session('current_institut_id', $user->institut_id);
            $proprietaire = Institut::where('instituts.id', $institutId)
                ->join('users', 'users.id', '=', 'instituts.proprietaire_id')
                ->select('users.email_verified_at', 'users.created_at')
                ->first();

            if ($proprietaire
                && !$proprietaire->email_verified_at
                && \Carbon\Carbon::parse($proprietaire->created_at)->diffInDays(now()) >= 3
            ) {
                return redirect()->route('verification.email')
                    ->with('bloque_par_proprietaire', true);
            }
        }

        return $next($request);
    }
}
