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
        $search        = $request->input('q');
        $segment       = $request->input('segment'); // nouveau | fidele | vip | inactif
        $pointsMin     = $request->integer('points_min');
        $moisAnniv     = $request->input('mois_anniv'); // 01..12
        $inactifJours  = $request->integer('inactif_jours'); // ex: 90

        $clients = Client::query()
            ->withCount('ventes')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('prenom', 'like', "%{$search}%")
                       ->orWhere('nom', 'like', "%{$search}%")
                       ->orWhere('telephone', 'like', "%{$search}%");
                });
            })
            ->when($pointsMin > 0, fn ($q) => $q->where('points_fidelite', '>=', $pointsMin))
            ->when($moisAnniv, fn ($q) => $q->whereNotNull('date_naissance')
                ->where('date_naissance', 'like', $moisAnniv . '-%'))
            ->when($segment === 'nouveau', fn ($q) => $q->where('created_at', '>=', now()->subDays(30)))
            ->when($segment === 'fidele', fn ($q) => $q
                ->whereHas('ventes', fn ($q2) => $q2->where('statut', 'validee'), '>=', 3)
                ->whereHas('ventes', fn ($q2) => $q2->where('statut', 'validee'), '<', 10))
            ->when($segment === 'vip', fn ($q) => $q
                ->whereHas('ventes', fn ($q2) => $q2->where('statut', 'validee'), '>=', 10))
            ->when($segment === 'inactif' || $inactifJours > 0, function ($q) use ($inactifJours) {
                $jours = $inactifJours > 0 ? $inactifJours : 90;
                $q->whereDoesntHave('ventes', function ($q2) use ($jours) {
                    $q2->where('statut', 'validee')
                       ->where('created_at', '>=', now()->subDays($jours));
                });
            })
            ->orderBy('prenom')
            ->paginate(25)
            ->withQueryString();

        // Clients fêtant leur anniversaire aujourd'hui sans cadeau déjà créé
        $cadeauClientIds = CodeReduction::withoutGlobalScopes()
            ->where('institut_id', $this->institutId())
            ->where('code', 'like', 'ANNIV-%')
            ->whereDate('date_debut', now()->toDateString())
            ->pluck('client_id')
            ->toArray();

        $anniversairesAujourdhui = Client::where('actif', true)
            ->where('date_naissance', now()->format('m-d'))
            ->whereNotIn('id', $cadeauClientIds)
            ->get();

        $statutAvis = $request->input('statut_avis');
        $avis = \App\Models\AvisClient::query()
            ->with(['rdv.prestations'])
            ->whereNotNull('repondu_le')
            ->when($statutAvis, fn ($q) => $q->where('statut', $statutAvis))
            ->orderByDesc('repondu_le')
            ->paginate(25)
            ->withQueryString();

        return view('dashboard.clients.index', compact('clients', 'search', 'anniversairesAujourdhui', 'avis', 'statutAvis'));
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

        $rdvAVenir = $client->rendezVous()
            ->with('prestations')
            ->where('debut_le', '>=', now())
            ->whereIn('statut', ['en_attente', 'confirme'])
            ->orderBy('debut_le')
            ->get();

        $rdvPasses = $client->rendezVous()
            ->with('prestations')
            ->where(fn ($q) => $q->where('debut_le', '<', now())->orWhereIn('statut', ['termine', 'annule']))
            ->orderByDesc('debut_le')
            ->take(10)
            ->get();

        // Timeline fusionnée (ventes + RDV + avis), triée DESC
        $timeline = collect();
        foreach ($client->ventes()->where('statut', 'validee')->latest()->take(50)->get() as $v) {
            $timeline->push([
                'type'  => 'vente',
                'date'  => $v->created_at,
                'titre' => 'Vente · ' . number_format($v->total, 0, ',', ' ') . ' F',
                'sous'  => $v->numero_facture ?? '#' . substr($v->id, 0, 8),
                'url'   => route('dashboard.ventes.show', $v),
                'icon'  => '💳',
            ]);
        }
        foreach ($client->rendezVous()->with('prestations')->latest('debut_le')->take(50)->get() as $r) {
            $timeline->push([
                'type'  => 'rdv',
                'date'  => $r->debut_le,
                'titre' => 'RDV · ' . ucfirst(str_replace('_', ' ', $r->statut)),
                'sous'  => $r->prestations->pluck('nom')->implode(', ') ?: '—',
                'url'   => route('dashboard.rdv.show', $r),
                'icon'  => '📅',
            ]);
        }
        foreach (\App\Models\AvisClient::where('client_id', $client->id)->whereNotNull('repondu_le')->get() as $a) {
            $timeline->push([
                'type'  => 'avis',
                'date'  => $a->repondu_le,
                'titre' => 'Avis ' . str_repeat('★', (int) $a->note),
                'sous'  => $a->commentaire ? \Illuminate\Support\Str::limit($a->commentaire, 80) : '—',
                'url'   => route('dashboard.avis.index'),
                'icon'  => '⭐',
            ]);
        }
        $timeline = $timeline->sortByDesc('date')->values();

        // Crédits du client
        $credits = $client->credits()->with(['vente.items'])->latest()->take(20)->get();

        return view('dashboard.clients.show', compact('client', 'ventes', 'rdvAVenir', 'rdvPasses', 'timeline', 'credits'));
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

    /** Résout un token fidélité en client_id (pour le scan QR à la caisse) */
    public function rechercheParTokenFidelite(Request $request)
    {
        $raw = trim((string) $request->input('token', ''));
        // Si on reçoit l'URL complète, extraire la dernière segment
        if (str_contains($raw, '/')) {
            $raw = trim(parse_url($raw, PHP_URL_PATH) ?? '', '/');
            $parts = explode('/', $raw);
            $raw = end($parts);
        }
        $client = Client::where('fidelite_token', $raw)->where('actif', true)->first();
        if (! $client) {
            return response()->json(['found' => false], 404);
        }
        return response()->json([
            'found'  => true,
            'id'     => $client->id,
            'nom'    => trim($client->prenom . ' ' . $client->nom),
            'points' => $client->points_fidelite,
        ]);
    }

    /** Régénère le token de la carte de fidélité (invalide l'ancien lien) */
    public function regenererTokenFidelite(Client $client)
    {
        abort_unless($client->institut_id === $this->institutId(), 403);
        $client->forceFill(['fidelite_token' => \Illuminate\Support\Str::random(40)])->save();
        return back()->with('success', 'Token de la carte de fidélité régénéré. L\'ancien lien n\'est plus valide.');
    }

    /** PDF carte de visite fidélité */
    public function carteFidelitePdf(Client $client)
    {
        abort_unless($client->institut_id === $this->institutId(), 403);
        $institut = \App\Models\Institut::find($client->institut_id);
        $lien = route('public.carte-fidelite', $client->fidelite_token);

        // Télécharge le QR en base64 (DomPDF n'accepte pas les images distantes par défaut)
        $qrBase64 = null;
        try {
            $ctx = stream_context_create(['http' => ['timeout' => 5]]);
            $png = @file_get_contents(
                'https://api.qrserver.com/v1/create-qr-code/?size=300x300&format=png&margin=0&data=' . urlencode($lien),
                false,
                $ctx
            );
            if ($png !== false) {
                $qrBase64 = 'data:image/png;base64,' . base64_encode($png);
            }
        } catch (\Throwable $e) {}

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.carte-fidelite', compact('client', 'institut', 'lien', 'qrBase64'))
            ->setPaper([0, 0, 240, 150]); // ~85x55mm (carte de visite)
        return $pdf->download('carte-fidelite-' . $client->id . '.pdf');
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
