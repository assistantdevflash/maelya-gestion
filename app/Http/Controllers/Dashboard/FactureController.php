<?php
namespace App\Http\Controllers\Dashboard;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Facture;
use App\Models\FactureItem;
use App\Models\Paiement;
use App\Services\DevisService;
use App\Services\FactureService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class FactureController extends Controller
{
    public function index(Request $request)
    {
        $institutId = session('current_institut_id', Auth::user()->institut_id);
        $query = Facture::where('institut_id', $institutId)->with(['client','devis'])->latest('date_emission');
        if ($request->filled('statut')) $query->where('statut', $request->statut);
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) { $q->where('numero','like',"%{$s}%")->orWhere('client_nom','like',"%{$s}%")->orWhere('client_prenom','like',"%{$s}%"); });
        }
        $factures = $query->paginate(20);
        $stats = ['total_ttc' => Facture::where('institut_id',$institutId)->sum('total_ttc'), 'total_paye' => Facture::where('institut_id',$institutId)->sum('montant_paye'), 'en_retard' => Facture::where('institut_id',$institutId)->where('statut','!=','payee')->whereDate('date_echeance','<',now())->count()];
        return view('dashboard.devis-factures.index', compact('factures','stats'))->with('tab','factures');
    }

    public function create(Request $request)
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
        $devis = null;
        if ($request->filled('devis_id')) {
            $devis = \App\Models\Devis::with('items')->find($request->devis_id);
        }
        return view('dashboard.devis-factures.factures.create', compact('allClients','devis'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'nullable|uuid|exists:clients,id',
            'client_prenom' => 'required_without:client_id|string|max:100',
            'client_nom' => 'required_without:client_id|string|max:100',
            'client_email' => 'nullable|email|max:255', 'client_telephone' => 'nullable|string|max:30', 'client_adresse' => 'nullable|string|max:500',
            'date_emission' => 'required|date', 'date_echeance' => 'required|date|after_or_equal:date_emission',
            'notes' => 'nullable|string|max:5000', 'conditions' => 'nullable|string|max:5000',
            'tva_applicable' => 'boolean', 'tva_taux' => 'nullable|numeric|min:0|max:100',
            'remise_globale_type' => 'nullable|in:pourcentage,montant_fixe', 'remise_globale_valeur' => 'nullable|integer|min:0',
            'lignes' => 'required|json',
        ]);
        $lignes = json_decode($data['lignes'], true);
        if (empty($lignes)) return back()->withInput()->withErrors(['lignes' => 'Ajoutez au moins une ligne.']);
        [$sousTotal, $remise, $totalHT, $tva, $totalTTC, $lignes] = DevisService::calculerTotaux($lignes, $data);
        $clientId = $data['client_id'] ?? null;
        if (!$clientId && !empty($data['client_telephone'])) {
            $client = Client::firstOrCreate(['telephone' => $data['client_telephone'], 'institut_id' => session('current_institut_id', Auth::user()->institut_id)], ['prenom' => $data['client_prenom'] ?? '', 'nom' => $data['client_nom'] ?? '']);
            $clientId = $client->id;
        }
        $facture = Facture::create([
            'institut_id' => session('current_institut_id', Auth::user()->institut_id),
            'client_id' => $clientId, 'user_id' => Auth::id(),
            'numero' => FactureService::genererNumero(), 'statut' => 'en_attente',
            'date_emission' => $data['date_emission'], 'date_echeance' => $data['date_echeance'],
            'client_prenom' => $data['client_prenom'] ?? null, 'client_nom' => $data['client_nom'] ?? null,
            'client_email' => $data['client_email'] ?? null, 'client_telephone' => $data['client_telephone'] ?? null, 'client_adresse' => $data['client_adresse'] ?? null,
            'sous_total' => $sousTotal, 'remise_globale_type' => $data['remise_globale_type'] ?? null, 'remise_globale_valeur' => $data['remise_globale_valeur'] ?? 0,
            'total_ht' => $totalHT, 'tva_applicable' => $data['tva_applicable'] ?? false, 'tva_taux' => $data['tva_taux'] ?? 0, 'total_ttc' => $totalTTC,
            'notes' => $data['notes'] ?? null, 'conditions' => $data['conditions'] ?? null, 'token' => Str::random(32),
        ]);
        foreach ($lignes as $i => $ligne) {
            FactureItem::create(['facture_id' => $facture->id, 'designation' => $ligne['designation'], 'quantite' => $ligne['quantite'], 'prix_unitaire' => $ligne['prix_unitaire'], 'remise_type' => $ligne['remise_type'] ?? null, 'remise_valeur' => $ligne['remise_valeur'] ?? 0, 'tva_taux' => $ligne['tva_taux'] ?? null, 'total_ligne' => $ligne['total_ligne'], 'ordre' => $i]);
        }
        return redirect()->route('dashboard.factures.show', $facture->id)->with('success','Facture créée.');
    }

    public function show(Facture $facture) { $facture->load(['items','client','devis','paiements.encaisseur','vente']); return view('dashboard.devis-factures.factures.show', compact('facture')); }

    public function pdf(Facture $facture) {
        $facture->load(['items','client']);
        $pdf = Pdf::loadView('pdf.facture-module', ['facture' => $facture, 'institut' => $facture->institut]);
        return $pdf->download("Facture-{$facture->numero}.pdf");
    }

    public function ajouterPaiement(Request $request, Facture $facture)
    {
        $data = $request->validate([
            'montant' => 'required|integer|min:1|max:'.$facture->resteAPayer,
            'mode_paiement' => 'required|in:especes,mobile_money,carte,virement,cheque',
            'reference' => 'nullable|string|max:100',
            'date_paiement' => 'required|date',
        ]);
        Paiement::create([
            'facture_id' => $facture->id,
            'montant' => $data['montant'],
            'mode_paiement' => $data['mode_paiement'],
            'reference' => $data['reference'] ?? null,
            'date_paiement' => $data['date_paiement'],
        ]);
        $facture->update(['montant_paye' => $facture->paiements()->sum('montant')]);
        if ($facture->fresh()->estPayee) $facture->update(['statut' => 'payee']);
        elseif ($facture->montant_paye > 0 && !$facture->fresh()->estPayee) $facture->update(['statut' => 'partiellement_payee']);
        return back()->with('success','Paiement enregistré.');
    }

    public function marquerPayee(Facture $facture) {
        if ($facture->estPayee) return back()->with('error','Cette facture est déjà payée.');
        FactureService::marquerPayee($facture);
        return back()->with('success','Facture marquée payée. Vente créée.');
    }

    public function annuler(Facture $facture) { $facture->update(['statut' => 'annulee']); return back()->with('success','Facture annulée.'); }
    public function destroy(Facture $facture) { $facture->delete(); return redirect()->route('dashboard.factures.index')->with('success','Facture supprimée.'); }
}
