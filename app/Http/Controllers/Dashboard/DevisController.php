<?php
namespace App\Http\Controllers\Dashboard;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Devis;
use App\Models\DevisItem;
use App\Services\DevisService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DevisController extends Controller
{
    public function index(Request $request)
    {
        $institutId = session('current_institut_id', Auth::user()->institut_id);
        $query = Devis::where('institut_id', $institutId)->with(['client','createur'])->latest('date_creation');
        if ($request->filled('statut')) $query->where('statut', $request->statut);
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('numero','like',"%{$s}%")->orWhere('client_nom','like',"%{$s}%")->orWhere('client_prenom','like',"%{$s}%");
            });
        }
        $devis = $query->paginate(20);
        $stats = ['en_cours' => Devis::where('institut_id',$institutId)->enCours()->count(), 'total_ttc' => Devis::where('institut_id',$institutId)->enCours()->sum('total_ttc'), 'acceptes' => Devis::where('institut_id',$institutId)->acceptes()->count()];
        $clients = Client::where('institut_id',$institutId)->orderBy('nom')->get();
        return view('dashboard.devis-factures.index', compact('devis','stats','clients'))->with('tab','devis');
    }

    public function create()
    {
        $institutId = session('current_institut_id', Auth::user()->institut_id);
        $allClients = Client::where('institut_id', $institutId)
            ->orderBy('nom')
            ->get()
            ->map(fn($c) => [
                'id'        => $c->id,
                'prenom'    => $c->prenom,
                'nom'       => $c->nom,
                'nom_affichage' => $c->nom_affichage,
                'telephone' => $c->telephone,
                'email'     => $c->email,
                'adresse'   => $c->adresse,
                'initiale'  => strtoupper(substr($c->prenom ?: $c->nom, 0, 1)),
                'search'    => strtolower($c->nom . ' ' . $c->prenom . ' ' . $c->telephone),
            ]);

        // Catalogue : prestations + produits existants
        $prestations = \App\Models\Prestation::where('institut_id', $institutId)->where('actif', true)
            ->orderBy('nom')->get(['id', 'nom', 'prix'])
            ->map(fn($p) => ['id' => 'p_'.$p->id, 'type' => 'prestation', 'designation' => $p->nom, 'prix' => $p->prix, 'search' => strtolower($p->nom)]);
        $produits = \App\Models\Produit::where('institut_id', $institutId)->where('actif', true)
            ->orderBy('nom')->get(['id', 'nom', 'prix_vente'])
            ->map(fn($p) => ['id' => 'prod_'.$p->id, 'type' => 'produit', 'designation' => $p->nom, 'prix' => $p->prix_vente, 'search' => strtolower($p->nom)]);
        $catalogue = $prestations->concat($produits)->values();

        return view('dashboard.devis-factures.devis.create', compact('allClients', 'catalogue'))
            ->with('duplicateData', session('duplicate_devis'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'nullable|uuid|exists:clients,id',
            'client_prenom' => 'required_without:client_id|string|max:100',
            'client_nom' => 'required_without:client_id|string|max:100',
            'client_email' => 'nullable|email|max:255',
            'client_telephone' => 'nullable|string|max:30',
            'client_adresse' => 'nullable|string|max:500',
            'date_creation' => 'required|date',
            'date_expiration' => 'required|date|after:date_creation',
            'notes' => 'nullable|string|max:5000',
            'conditions' => 'nullable|string|max:5000',
            'titre' => 'nullable|string|max:200',
            'tva_applicable' => 'boolean',
            'tva_taux' => 'nullable|numeric|min:0|max:100',
            'remise_globale_type' => 'nullable|in:pourcentage,montant_fixe',
            'remise_globale_valeur' => 'nullable|integer|min:0',
            'lignes' => 'required|json',
        ]);
        $lignes = json_decode($data['lignes'], true);
        if (empty($lignes)) return back()->withInput()->withErrors(['lignes' => 'Ajoutez au moins une ligne.']);
        [$sousTotal, $remise, $totalHT, $tva, $totalTTC, $lignes] = DevisService::calculerTotaux($lignes, $data);
        if ($totalTTC <= 0) return back()->withInput()->withErrors(['lignes' => 'Le montant total doit être supérieur à 0 F.']);
        $clientId = $data['client_id'] ?? null;
        // Si un client existant est sélectionné, récupérer ses infos pour le snapshot
        if ($clientId) {
            $client = Client::find($clientId);
            if ($client) {
                $data['client_prenom'] = $data['client_prenom'] ?? $client->prenom;
                $data['client_nom'] = $data['client_nom'] ?? $client->nom;
                $data['client_telephone'] = $data['client_telephone'] ?? $client->telephone;
                $data['client_email'] = $data['client_email'] ?? $client->email;
                $data['client_adresse'] = $data['client_adresse'] ?? $client->adresse;
            }
        } elseif (!$clientId && !empty($data['client_telephone'])) {
            // Vérifier si un client existe déjà avec le même téléphone ou email
            $existing = Client::where('institut_id', session('current_institut_id', Auth::user()->institut_id))
                ->where(function ($q) use ($data) {
                    $q->where('telephone', $data['client_telephone']);
                    if (!empty($data['client_email'])) {
                        $q->orWhere('email', $data['client_email']);
                    }
                })->first();
            if ($existing) {
                $clientId = $existing->id;
            } else {
                $client = Client::create([
                    'telephone' => $data['client_telephone'],
                    'institut_id' => session('current_institut_id', Auth::user()->institut_id),
                    'prenom' => $data['client_prenom'] ?? '',
                    'nom' => $data['client_nom'] ?? '',
                    'email' => $data['client_email'] ?? null,
                ]);
                $clientId = $client->id;
            }
        }
        $devis = Devis::create([
            'institut_id' => session('current_institut_id', Auth::user()->institut_id),
            'client_id' => $clientId, 'user_id' => Auth::id(),
            'numero' => DevisService::genererNumero(), 'statut' => 'brouillon',
            'date_creation' => $data['date_creation'], 'date_expiration' => $data['date_expiration'],
            'client_prenom' => $data['client_prenom'] ?? null, 'client_nom' => $data['client_nom'] ?? null,
            'client_email' => $data['client_email'] ?? null, 'client_telephone' => $data['client_telephone'] ?? null, 'client_adresse' => $data['client_adresse'] ?? null,
            'sous_total' => $sousTotal, 'remise_globale_type' => $data['remise_globale_type'] ?? null, 'remise_globale_valeur' => $data['remise_globale_valeur'] ?? 0,
            'total_ht' => $totalHT, 'tva_applicable' => $data['tva_applicable'] ?? false, 'tva_taux' => $data['tva_taux'] ?? 0, 'total_ttc' => $totalTTC,
            'notes' => $data['notes'] ?? null, 'conditions' => $data['conditions'] ?? null, 'titre' => $data['titre'] ?? null,
        ]);
        foreach ($lignes as $i => $ligne) {
            DevisItem::create(['devis_id' => $devis->id, 'designation' => $ligne['designation'], 'quantite' => $ligne['quantite'], 'prix_unitaire' => $ligne['prix_unitaire'], 'remise_type' => $ligne['remise_type'] ?? null, 'remise_valeur' => $ligne['remise_valeur'] ?? 0, 'tva_taux' => $ligne['tva_taux'] ?? null, 'total_ligne' => $ligne['total_ligne'], 'ordre' => $i]);
        }
        return redirect()->route('dashboard.devis.show', ['devis' => $devis->id])->with('success', 'Devis créé avec succès.');
    }

    public function show(Devis $devis) { $devis->load(['items','client','createur','facture']); return view('dashboard.devis-factures.devis.show', compact('devis')); }
    public function edit(Devis $devis)
    {
        $devis->load('items');
        $institutId = session('current_institut_id', Auth::user()->institut_id);
        $prestations = \App\Models\Prestation::where('institut_id', $institutId)->where('actif', true)
            ->orderBy('nom')->get(['id', 'nom', 'prix'])
            ->map(fn($p) => ['id' => 'p_'.$p->id, 'type' => 'prestation', 'designation' => $p->nom, 'prix' => $p->prix, 'search' => strtolower($p->nom)]);
        $produits = \App\Models\Produit::where('institut_id', $institutId)->where('actif', true)
            ->orderBy('nom')->get(['id', 'nom', 'prix_vente'])
            ->map(fn($p) => ['id' => 'prod_'.$p->id, 'type' => 'produit', 'designation' => $p->nom, 'prix' => $p->prix_vente, 'search' => strtolower($p->nom)]);
        $catalogue = $prestations->concat($produits)->values();
        return view('dashboard.devis-factures.devis.edit', compact('devis', 'catalogue'));
    }

    public function update(Request $request, Devis $devis)
    {
        if (!$devis->estModifiable) return back()->with('error','Ce devis ne peut plus être modifié.');
        $data = $request->validate([
            'date_expiration' => 'required|date|after:date_creation', 'notes' => 'nullable|string|max:5000', 'conditions' => 'nullable|string|max:5000', 'titre' => 'nullable|string|max:200',
            'tva_applicable' => 'boolean', 'tva_taux' => 'nullable|numeric|min:0|max:100',
            'remise_globale_type' => 'nullable|in:pourcentage,montant_fixe', 'remise_globale_valeur' => 'nullable|integer|min:0',
            'lignes' => 'required|json',
        ]);
        $lignes = json_decode($data['lignes'], true);
        if (empty($lignes)) return back()->withInput()->withErrors(['lignes' => 'Ajoutez au moins une ligne.']);
        [$sousTotal, $remise, $totalHT, $tva, $totalTTC, $lignes] = DevisService::calculerTotaux($lignes, $data);
        if ($totalTTC <= 0) return back()->withInput()->withErrors(['lignes' => 'Le montant total doit être supérieur à 0 F.']);
        $devis->items()->delete();
        foreach ($lignes as $i => $ligne) {
            DevisItem::create(['devis_id' => $devis->id, 'designation' => $ligne['designation'], 'quantite' => $ligne['quantite'], 'prix_unitaire' => $ligne['prix_unitaire'], 'remise_type' => $ligne['remise_type'] ?? null, 'remise_valeur' => $ligne['remise_valeur'] ?? 0, 'tva_taux' => $ligne['tva_taux'] ?? null, 'total_ligne' => $ligne['total_ligne'], 'ordre' => $i]);
        }
        $devis->update(array_merge($data, ['sous_total' => $sousTotal, 'remise_globale_type' => $data['remise_globale_type'] ?? null, 'remise_globale_valeur' => $data['remise_globale_valeur'] ?? 0, 'total_ht' => $totalHT, 'total_ttc' => $totalTTC, 'tva_applicable' => $data['tva_applicable'] ?? false, 'tva_taux' => $data['tva_taux'] ?? 0]));
        return redirect()->route('dashboard.devis.show', ['devis' => $devis->id])->with('success','Devis mis à jour.');
    }

    public function destroy(Devis $devis) { $devis->delete(); return redirect()->route('dashboard.devis.index')->with('success','Devis supprimé.'); }

    public function envoyer(Devis $devis) { $devis->update(['statut' => 'envoye', 'token' => Str::random(32)]); return back()->with('success','Devis marqué comme envoyé.'); }

    public function transformerEnFacture(Devis $devis) {
        if ($devis->facture_id) return back()->with('error','Ce devis a déjà été transformé.');
        $facture = DevisService::transformerEnFacture($devis);
        return redirect()->route('dashboard.factures.show', ['facture' => $facture->id])->with('success','Facture créée à partir du devis.');
    }

    public function pdf(Devis $devis) {
        $devis->load(['items','client']);
        $pdf = Pdf::loadView('pdf.devis', ['devis' => $devis, 'institut' => $devis->institut]);
        return $pdf->download("Devis-{$devis->numero}.pdf");
    }

    public function dupliquer(Devis $devis)
    {
        // Stocker les données du devis en session pour pré-remplir le formulaire de création
        session()->flash('duplicate_devis', [
            'client_id' => $devis->client_id,
            'client' => $devis->client ? [
                'id' => $devis->client->id,
                'prenom' => $devis->client->prenom,
                'nom' => $devis->client->nom,
                'nom_affichage' => $devis->client->nom_affichage,
                'telephone' => $devis->client->telephone,
                'email' => $devis->client->email,
                'adresse' => $devis->client->adresse,
                'initiale' => strtoupper(substr($devis->client->prenom ?: $devis->client->nom, 0, 1)),
            ] : null,
            'date_expiration' => $devis->date_expiration->toDateString(),
            'notes' => $devis->notes,
            'tva_applicable' => $devis->tva_applicable,
            'tva_taux' => $devis->tva_taux,
            'remise_globale_type' => $devis->remise_globale_type,
            'remise_globale_valeur' => $devis->remise_globale_valeur,
            'lignes' => $devis->items->map(fn($i) => [
                'designation' => $i->designation,
                'quantite' => $i->quantite,
                'prix_unitaire' => $i->prix_unitaire,
                'remise_type' => $i->remise_type,
                'remise_valeur' => $i->remise_valeur,
                'tva_taux' => $i->tva_taux,
            ])->toArray(),
        ]);

        return redirect()->route('dashboard.devis.create')->with('success', 'Données du devis '.$devis->numero.' prêtes. Vérifiez et créez le nouveau devis.');
    }

    /** Envoyer le devis par email au client */
    public function envoyerEmail(Devis $devis)
    {
        $clientEmail = $devis->client_email ?: ($devis->client->email ?? null);
        if (!$clientEmail) return back()->with('error', 'Aucune adresse email pour ce client.');

        $devis->load('items', 'client', 'institut');
        $institut = $devis->institut;
        $pdf = Pdf::loadView('pdf.devis', ['devis' => $devis, 'institut' => $institut]);

        \Illuminate\Support\Facades\Mail::send('emails.devis', ['devis' => $devis], function ($message) use ($devis, $institut, $pdf, $clientEmail) {
            $message->to($clientEmail, $devis->client_nom_complet ?: ($devis->client->nom_complet ?? 'Client'))
                    ->subject('Devis ' . $devis->numero . ' — ' . ($institut?->nom ?? 'Maelya Gestion'))
                    ->attachData($pdf->output(), "devis-{$devis->numero}.pdf", ['mime' => 'application/pdf']);
        });

        if ($devis->statut === 'brouillon') {
            $devis->update(['statut' => 'envoye', 'token' => \Illuminate\Support\Str::random(32)]);
        }

        return back()->with('success', 'Devis envoyé par email à ' . ($devis->client_nom_complet ?: 'votre client') . '.');
    }
}
