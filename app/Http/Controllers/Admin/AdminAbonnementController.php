<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Abonnement;
use App\Models\Parrainage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAbonnementController extends Controller
{
    public function index(Request $request)
    {
        $abonnements = Abonnement::with('user.institut', 'plan')
            ->when($request->statut, fn($q) => $q->where('statut', $request->statut))
            ->when($request->q, fn($q, $search) => $q->whereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $stats = [
            'en_attente' => Abonnement::where('statut', 'en_attente')->count(),
            'actif' => Abonnement::where('statut', 'actif')->where('expire_le', '>=', now())->count(),
            'expire' => Abonnement::where('statut', 'expire')->orWhere(fn($q) => $q->where('statut', 'actif')->where('expire_le', '<', now()))->count(),
        ];

        return view('admin.abonnements.index', compact('abonnements', 'stats'));
    }

    public function show(Abonnement $abonnement)
    {
        $abonnement->load('user.institut', 'user.mesInstituts', 'plan', 'validePar');

        // Charger le parrainage lié à cet utilisateur (filleul)
        $parrainage = Parrainage::where('filleul_id', $abonnement->user_id)
            ->with('parrain.institut')
            ->first();

        return view('admin.abonnements.show', compact('abonnement', 'parrainage'));
    }

    public function valider(Abonnement $abonnement)
    {
        if ($abonnement->statut !== 'en_attente') {
            return back()->with('error', 'Ce n\'est pas une demande en attente.');
        }

        $plan = $abonnement->plan;
        $jours = $plan->joursPourPeriode($abonnement->periode);

        // Expirer les abonnements actifs précédents de cet utilisateur
        Abonnement::where('user_id', $abonnement->user_id)
            ->where('id', '!=', $abonnement->id)
            ->where('statut', 'actif')
            ->update(['statut' => 'expire']);

        $abonnement->update([
            'statut' => 'actif',
            'debut_le' => now()->toDateString(),
            'expire_le' => now()->addDays($jours)->toDateString(),
            'valide_par' => Auth::id(),
        ]);

        // ── Récompense de parrainage ──────────────────────────────────────────
        $parrainage = Parrainage::where('filleul_id', $abonnement->user_id)
            ->where('statut', 'en_attente')
            ->first();

        if ($parrainage) {
            $parrainage->update(['statut' => 'valide']);

            // Bonus parrain : prolonger son abonnement actif
            $aboParrain = Abonnement::where('user_id', $parrainage->parrain_id)
                ->where('statut', 'actif')
                ->where('expire_le', '>=', now()->toDateString())
                ->latest('expire_le')
                ->first();

            if ($aboParrain) {
                $aboParrain->update([
                    'expire_le' => $aboParrain->expire_le->addDays($parrainage->jours_offerts_parrain)->toDateString(),
                    'notes_admin' => ($aboParrain->notes_admin ? $aboParrain->notes_admin . "\n" : '')
                        . 'Parrainage : +' . $parrainage->jours_offerts_parrain . ' jours le ' . now()->format('d/m/Y'),
                ]);
            }

            // Bonus filleul : prolonger l'abonnement qu'on vient de valider
            $abonnement->update([
                'expire_le' => $abonnement->fresh()->expire_le->addDays($parrainage->jours_offerts_filleul)->toDateString(),
            ]);
        }

        return back()->with('success', "Abonnement validé ! Actif jusqu'au " . $abonnement->fresh()->expire_le->format('d/m/Y') . '.' . ($parrainage ? ' Bonus parrainage appliqué.' : ''));
    }

    public function rejeter(Request $request, Abonnement $abonnement)
    {
        if ($abonnement->statut !== 'en_attente') {
            return back()->with('error', 'Ce n\'est pas une demande en attente.');
        }

        $request->validate(['notes_admin' => ['nullable', 'string', 'max:500']]);

        $abonnement->update([
            'statut' => 'rejete',
            'notes_admin' => $request->notes_admin,
            'valide_par' => Auth::id(),
        ]);

        return back()->with('success', 'Demande rejetée.');
    }

    public function prolongerEssai(Request $request, Abonnement $abonnement)
    {
        $request->validate(['jours' => ['required', 'integer', 'min:1', 'max:90']]);

        $newExpire = ($abonnement->expire_le && $abonnement->expire_le->isFuture())
            ? $abonnement->expire_le->addDays($request->jours)
            : now()->addDays($request->jours);

        $abonnement->update([
            'statut' => 'actif',
            'expire_le' => $newExpire->toDateString(),
            'debut_le' => $abonnement->debut_le ?? now()->toDateString(),
            'notes_admin' => ($abonnement->notes_admin ? $abonnement->notes_admin . "\n" : '') . 'Prolongé de ' . $request->jours . ' jours le ' . now()->format('d/m/Y'),
        ]);

        return back()->with('success', "Essai prolongé de {$request->jours} jours.");
    }
}
