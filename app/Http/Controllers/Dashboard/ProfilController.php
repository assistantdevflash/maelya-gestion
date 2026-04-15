<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class ProfilController extends Controller
{
    public function edit()
    {
        return view('dashboard.profil', ['user' => Auth::user()]);
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
