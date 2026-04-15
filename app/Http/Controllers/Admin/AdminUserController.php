<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('institut')
            ->when($request->q, fn($q, $search) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('telephone', 'like', "%{$search}%")
            )
            ->when($request->role, fn($q, $role) => $q->where('role', $role))
            ->orderBy('created_at', 'desc')
            ->paginate(30)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'prenom'      => ['required', 'string', 'max:80'],
            'nom_famille' => ['required', 'string', 'max:80'],
            'email'       => ['required', 'email', 'max:180', 'unique:users,email'],
            'telephone'   => ['nullable', 'string', 'max:30'],
            'password'    => ['required', Password::min(8)],
        ]);

        $data['name']     = trim($data['prenom'] . ' ' . $data['nom_famille']);
        $data['password'] = Hash::make($data['password']);
        $data['role']     = 'super_admin';
        $data['actif']    = true;

        User::create($data);

        return back()->with('success', 'Collaborateur créé.');
    }

    public function toggleActif(User $user)
    {
        if ($user->isSuperAdmin()) {
            return back()->with('error', 'Impossible de désactiver un super admin.');
        }

        $user->update(['actif' => !$user->actif]);

        return back()->with('success', $user->actif ? 'Compte activé.' : 'Compte désactivé.');
    }
}
