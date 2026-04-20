<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\CodeReduction;
use App\Models\Vente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ClientController extends Controller
{
    private function institutId(): string
    {
        return session('current_institut_id', Auth::user()->institut_id);
    }

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

        // Clients fêtant leur anniversaire aujourd'hui sans cadeau déjà créé
        $cadeauClientIds = CodeReduction::withoutGlobalScopes()
            ->where('institut_id', $this->institutId())
            ->where('code', 'like', 'ANNIV-%')
            ->where('date_debut', now()->toDateString())
            ->pluck('client_id')
            ->toArray();

        $anniversairesAujourdhui = Client::where('actif', true)
            ->where('date_naissance', now()->format('m-d'))
            ->whereNotIn('id', $cadeauClientIds)
            ->get();

        return view('dashboard.clients.index', compact('clients', 'search', 'anniversairesAujourdhui'));
    }

    public function create()
    {
        return view('dashboard.clients.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'prenom'         => ['required', 'string', 'max:50'],
            'nom'            => ['required', 'string', 'max:50'],
            'telephone'      => ['required', 'string', 'max:30'],
            'email'          => ['nullable', 'email', 'max:255'],
            'date_naissance' => ['nullable', 'regex:/^\d{2}-\d{2}$/'],
            'notes'          => ['nullable', 'string', 'max:1000'],
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

    public function update(Request $request, Client $client)
    {
        $data = $request->validate([
            'prenom'         => ['required', 'string', 'max:50'],
            'nom'            => ['required', 'string', 'max:50'],
            'telephone'      => ['required', 'string', 'max:30'],
            'email'          => ['nullable', 'email', 'max:255'],
            'date_naissance' => ['nullable', 'regex:/^\d{2}-\d{2}$/'],
            'notes'          => ['nullable', 'string', 'max:1000'],
        ]);

        $client->update($data);

        return redirect()->back()
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

    /**
     * Crée un code de réduction d'anniversaire pour un client.
     */
    public function cadeauAnniversaire(Request $request, Client $client)
    {
        abort_unless($client->institut_id === $this->institutId(), 403);

        $data = $request->validate([
            'type'   => ['required', 'in:pourcentage,montant_fixe'],
            'valeur' => ['required', 'integer', 'min:1'],
        ]);

        $code = 'ANNIV-' . strtoupper(Str::slug($client->prenom)) . '-' . strtoupper(Str::random(4));

        // S'assurer de l'unicité
        while (CodeReduction::where('institut_id', $this->institutId())->where('code', $code)->exists()) {
            $code = 'ANNIV-' . strtoupper(Str::slug($client->prenom)) . '-' . strtoupper(Str::random(4));
        }

        CodeReduction::create([
            'institut_id'        => $this->institutId(),
            'client_id'          => $client->id,
            'code'               => $code,
            'description'        => 'Cadeau anniversaire — ' . $client->nom_complet,
            'type'               => $data['type'],
            'valeur'             => $data['valeur'],
            'date_debut'         => now()->toDateString(),
            'date_fin'           => now()->addDays(30)->toDateString(),
            'limite_utilisation' => 1,
            'actif'              => true,
        ]);

        return back()->with('success', "Cadeau créé ! Code : {$code} (valable 30 jours)");
    }
}
