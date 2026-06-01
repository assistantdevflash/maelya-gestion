<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class ProfilController extends Controller
{
    public function edit(Request $request)
    {
        $data = ['user' => Auth::user()];

        if (Auth::user()->isAdmin()) {
            $institutId = session('current_institut_id', Auth::user()->institut_id);
            $query = AuditLog::with('user:id,prenom,nom_famille')
                ->where('institut_id', $institutId);

            if ($request->filled('log_action')) {
                $query->where('action', $request->log_action);
            }
            if ($request->filled('log_type')) {
                $query->where('subject_type', $request->log_type);
            }
            if ($request->filled('log_q')) {
                $q = $request->log_q;
                $query->where(fn($w) => $w->where('label', 'like', "%$q%")->orWhere('subject_id', $q));
            }

            $data['logs']        = $query->latest()->paginate(30)->withQueryString();
            $data['logActions']  = AuditLog::where('institut_id', $institutId)->select('action')->distinct()->pluck('action');
            $data['logSubjects'] = AuditLog::where('institut_id', $institutId)->select('subject_type')->whereNotNull('subject_type')->distinct()->pluck('subject_type');

            // Maps id → nom pour résoudre les UUIDs dans les champs de changements
            $data['usersMap']   = User::where('institut_id', $institutId)
                ->get(['id', 'prenom', 'nom_famille'])
                ->mapWithKeys(fn($u) => [$u->id => trim($u->prenom . ' ' . $u->nom_famille)])
                ->all();
            $data['clientsMap'] = Client::where('institut_id', $institutId)
                ->withTrashed()
                ->get(['id', 'prenom', 'nom'])
                ->mapWithKeys(fn($c) => [$c->id => trim($c->prenom . ' ' . $c->nom)])
                ->all();
        }

        return view('dashboard.profil', $data);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $rules = [
            'prenom' => ['required', 'string', 'max:50'],
            'nom_famille' => ['required', 'string', 'max:50'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'email' => ['required', 'email', 'unique:users,email,' . $user->id],
        ];

        if ($request->filled('password')) {
            $rules['password_actuel'] = ['required'];
            $rules['password'] = ['required', 'confirmed', Rules\Password::min(8)];
        }

        $data = $request->validate($rules);

        if ($request->filled('password')) {
            if (!Hash::check($request->password_actuel, $user->password)) {
                return back()->withErrors(['password_actuel' => 'Mot de passe actuel incorrect.']);
            }
            $user->update(['password' => Hash::make($request->password)]);
        }

        $user->update([
            'prenom' => $data['prenom'],
            'nom_famille' => $data['nom_famille'],
            'name' => $data['prenom'] . ' ' . $data['nom_famille'],
            'telephone' => $data['telephone'] ?? null,
            'email' => $data['email'],
        ]);

        return back()->with('success', 'Profil mis à jour.');
    }
}
