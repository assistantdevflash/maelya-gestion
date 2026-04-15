<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Vente;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('q');
        $clients = Client::query()
            ->withCount('ventes')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('prenom', 'like', "%{$search}%")
                       ->orWhere('nom', 'like', "%{$search}%")
                       ->orWhere('telephone', 'like', "%{$search}%");
                });
            })
            ->orderBy('prenom')
            ->paginate(25)
            ->withQueryString();

        return view('dashboard.clients.index', compact('clients', 'search'));
    }

    public function create()
    {
        return view('dashboard.clients.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'prenom' => ['required', 'string', 'max:50'],
            'nom' => ['required', 'string', 'max:50'],
            'telephone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'date_naissance' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        Client::create($data);

        return redirect()->route('dashboard.clients.index')
            ->with('success', 'Client ajouté avec succès.');
    }

    public function show(Client $client)
    {
        $ventes = $client->ventes()->with('items')->where('statut', 'validee')->latest()->paginate(20);
        return view('dashboard.clients.show', compact('client', 'ventes'));
    }

    public function edit(Client $client)
    {
        return view('dashboard.clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $data = $request->validate([
            'prenom' => ['required', 'string', 'max:50'],
            'nom' => ['required', 'string', 'max:50'],
            'telephone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'date_naissance' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $client->update($data);

        return redirect()->route('dashboard.clients.index')
            ->with('success', 'Client mis à jour.');
    }

    public function destroy(Client $client)
    {
        $client->delete();
        return redirect()->route('dashboard.clients.index')
            ->with('success', 'Client supprimé.');
    }

    public function archiver(Client $client)
    {
        $client->update(['actif' => !$client->actif]);
        $msg = $client->actif ? 'Client réactivé.' : 'Client archivé.';
        return back()->with('success', $msg);
    }
}
