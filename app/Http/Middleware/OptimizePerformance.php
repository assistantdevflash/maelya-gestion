<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OptimizePerformance
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Ajouter les headers de performance
        if ($response instanceof Response) {
            // Cache pour les assets statiques
            if ($request->is('build/*') || $request->is('assets/*')) {
                $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
            }

            // Compression
            if (!$response->headers->has('Content-Encoding')) {
                $response->headers->set('X-Compress-Hint', 'on');
            }

            // ⚠️ Ne PAS ajouter de header Link/rel=preload global :
            // `</build/assets>; rel=preload; as=style` faisait précharger un
            // dossier inexistant sur CHAQUE page (erreurs réseau + ralentissement).

            // Security headers qui n'impactent pas les performances
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        }

        return $response;
    }
}
