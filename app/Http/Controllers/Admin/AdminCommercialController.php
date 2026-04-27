<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommercialCommission;
use App\Models\CommercialProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

class AdminCommercialController extends Controller
{
    // ── Liste des commerciaux ─────────────────────────────────────────────────
    public function index(Request $request)
    {
        $commerciaux = User::where('role', 'commercial')
            ->with('commercialProfile')
            ->when($request->q, fn($q, $search) =>
                $q->where(fn($u) => $u
                    ->where('prenom', 'like', "%{$search}%")
                    ->orWhere('nom_famille', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                )
            )
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $config = DB::table('commercial_config')->first();

        $stats = [
            'total'       => User::where('role', 'commercial')->count(),
            'commissions' => CommercialCommission::sum('montant'),
            'en_attente'  => CommercialCommission::where('statut', 'en_attente')->sum('montant'),
        ];

        return view('admin.commerciaux.index', compact('commerciaux', 'config', 'stats'));
    }

    // ── Détail d'un commercial ────────────────────────────────────────────────
    public function show(User $commercial)
    {
        abort_unless($commercial->role === 'commercial', 404);

        $profil = $commercial->commercialProfile()->with([
            'parrainages.proprietaire.institut',
            'commissions.abonnement.plan',
        ])->firstOrFail();

        return view('admin.commerciaux.show', compact('commercial', 'profil'));
    }

    // ── Créer un commercial ───────────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'prenom'      => ['required', 'string', 'max:50'],
            'nom_famille' => ['required', 'string', 'max:50'],
            'email'       => ['required', 'email', 'unique:users,email'],
            'telephone'   => ['nullable', 'string', 'max:30'],
            'password'    => ['required', Rules\Password::min(8)],
            'notes'       => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($request) {
            $user = User::create([
                'prenom'      => $request->prenom,
                'nom_famille' => $request->nom_famille,
                'name'        => $request->prenom . ' ' . $request->nom_famille,
                'email'       => $request->email,
                'telephone'   => $request->telephone,
                'password'    => Hash::make($request->password),
                'role'        => 'commercial',
                'actif'       => true,
            ]);

            CommercialProfile::create([
                'user_id'   => $user->id,
                'code'      => $this->generateCode($request->prenom, $request->nom_famille),
                'telephone' => $request->telephone,
                'notes'     => $request->notes,
            ]);
        });

        return back()->with('success', 'Commercial créé avec succès.');
    }

    // ── Mettre à jour ─────────────────────────────────────────────────────────
    public function update(Request $request, User $commercial)
    {
        abort_unless($commercial->role === 'commercial', 404);

        $request->validate([
            'prenom'      => ['required', 'string', 'max:50'],
            'nom_famille' => ['required', 'string', 'max:50'],
            'telephone'   => ['nullable', 'string', 'max:30'],
            'notes'       => ['nullable', 'string', 'max:500'],
        ]);

        $commercial->update([
            'prenom'      => $request->prenom,
            'nom_famille' => $request->nom_famille,
            'name'        => $request->prenom . ' ' . $request->nom_famille,
            'telephone'   => $request->telephone,
        ]);

        $commercial->commercialProfile?->update([
            'telephone' => $request->telephone,
            'notes'     => $request->notes,
        ]);

        return back()->with('success', 'Commercial mis à jour.');
    }

    // ── Activer/Désactiver ────────────────────────────────────────────────────
    public function toggle(User $commercial)
    {
        abort_unless($commercial->role === 'commercial', 404);
        $commercial->update(['actif' => !$commercial->actif]);

        return back()->with('success', $commercial->actif ? 'Commercial activé.' : 'Commercial désactivé.');
    }

    // ── Supprimer ─────────────────────────────────────────────────────────────
    public function destroy(User $commercial)
    {
        abort_unless($commercial->role === 'commercial', 404);
        $commercial->delete();

        return redirect()->route('admin.commerciaux.index')->with('success', 'Commercial supprimé.');
    }

    // ── Marquer commission payée ──────────────────────────────────────────────
    public function payerCommission(CommercialCommission $commission)
    {
        if ($commission->statut === 'payee') {
            return back()->with('error', 'Déjà marquée comme payée.');
        }
        $commission->update(['statut' => 'payee', 'payee_le' => now()]);

        return back()->with('success', 'Commission marquée comme payée.');
    }

    // ── Annuler paiement ──────────────────────────────────────────────────────
    public function annulerPaiement(CommercialCommission $commission)
    {
        $commission->update(['statut' => 'en_attente', 'payee_le' => null]);

        return back()->with('success', 'Paiement annulé.');
    }

    // ── Sauvegarder la config ─────────────────────────────────────────────────
    public function updateConfig(Request $request)
    {
        $request->validate([
            'taux'       => ['required', 'integer', 'min:1', 'max:100'],
            'duree_mois' => ['required', 'integer', 'min:1', 'max:60'],
        ]);

        DB::table('commercial_config')->update([
            'taux'       => $request->taux,
            'duree_mois' => $request->duree_mois,
        ]);

        return back()->with('success', 'Configuration mise à jour.');
    }

    // ── Générer un code unique ────────────────────────────────────────────────
    private function generateCode(string $prenom, string $nom): string
    {
        $base = strtoupper(mb_substr($prenom, 0, 2) . mb_substr($nom, 0, 2));
        $base = preg_replace('/[^A-Z]/', '', $base) ?: 'COM';

        for ($i = 0; $i < 20; $i++) {
            $code = $base . rand(1000, 9999);
            if (!CommercialProfile::where('code', $code)->exists()) {
                return $code;
            }
        }

        return strtoupper(Str::random(6));
    }
}
