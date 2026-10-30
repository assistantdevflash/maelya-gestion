<?php

namespace App\Http\Controllers;

use App\Models\Credit;
use App\Scopes\InstitutScope;
use App\Services\CreditService;

/**
 * Page publique de suivi d'un crédit, accessible au client via un lien signé.
 * Aucune authentification : le lien ne peut pas être falsifié ni deviné.
 */
class CreditPublicController extends Controller
{
    public function show(string $id, CreditService $service)
    {
        $credit = Credit::withoutGlobalScope(InstitutScope::class)
            ->whereKey($id)
            ->with(['client', 'institut', 'vente.items', 'echeances', 'paiements'])
            ->firstOrFail();

        return view('public.suivi-credit', [
            'credit'    => $credit,
            'institut'  => $credit->institut,
            'service'   => $service,
            'urlFiche'  => $service->urlFiche($credit),
        ]);
    }
}
