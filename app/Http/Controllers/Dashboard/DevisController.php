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
        return view('dashboard.devis-factures.devis.create', compact('allClients'));
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
        if (!$clientId && !empty($data['client_telephone'])) {
            $client = Client::firstOrCreate(['telephone' => $data['client_telephone'], 'institut_id' => session('current_institut_id', Auth::user()->institut_id)], ['prenom' => $data['client_prenom'] ?? '', 'nom' => $data['client_nom'] ?? '']);
            $clientId = $client->id;
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
            'notes' => $data['notes'] ?? null, 'conditions' => $data['conditions'] ?? null,
        ]);
        foreach ($lignes as $i => $ligne) {
            DevisItem::create(['devis_id' => $devis->id, 'designation' => $ligne['designation'], 'quantite' => $ligne['quantite'], 'prix_unitaire' => $ligne['prix_unitaire'], 'remise_type' => $ligne['remise_type'] ?? null, 'remise_valeur' => $ligne['remise_valeur'] ?? 0, 'tva_taux' => $ligne['tva_taux'] ?? null, 'total_ligne' => $ligne['total_ligne'], 'ordre' => $i]);
        }
        return redirect()->route('dashboard.devis.show', ['devis' => $devis->id])->with('success', 'Devis créé avec succès.');
    }

    public function show(Devis $devis) { $devis->load(['items','client','createur','facture']); return view('dashboard.devis-factures.devis.show', compact('devis')); }
    public function edit(Devis $devis) { $devis->load('items'); return view('dashboard.devis-factures.devis.edit', compact('devis')); }

    public function update(Request $request, Devis $devis)
    {
        if (!$devis->estModifiable) return back()->with('error','Ce devis ne peut plus être modifié.');
        $data = $request->validate([
            'date_expiration' => 'required|date|after:date_creation', 'notes' => 'nullable|string|max:5000', 'conditions' => 'nullable|string|max:5000',
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

    public function dupliquer(Devis $devis) {
        $new = $devis->replicate(); $new->numero = DevisService::genererNumero(); $new->statut = 'brouillon'; $new->facture_id = null; $new->token = null; $new->save();
        foreach ($devis->items as $item) { DevisItem::create(['devis_id' => $new->id, 'designation' => $item->designation, 'quantite' => $item->quantite, 'prix_unitaire' => $item->prix_unitaire, 'remise_type' => $item->remise_type, 'remise_valeur' => $item->remise_valeur, 'tva_taux' => $item->tva_taux, 'total_ligne' => $item->total_ligne, 'ordre' => $item->ordre]); }
        return redirect()->route('dashboard.devis.show', ['devis' => $new->id])->with('success','Devis dupliqué.');
    }
}
