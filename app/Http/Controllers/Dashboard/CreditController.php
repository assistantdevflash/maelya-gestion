<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Credit;
use App\Models\Echeance;
use App\Models\Institut;
use App\Models\PaiementCredit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreditController extends Controller
{
    private function institutId(): string
    {
        return session('current_institut_id', Auth::user()->institut_id);
    }

    public function index(Request $request)
    {
        $filtre = $request->input('statut', 'tous');
        $search = $request->input('q');

        $credits = Credit::where('institut_id', $this->institutId())
            ->with(['client', 'vente.items'])
            ->when($filtre !== 'tous', fn($q) => $q->where('statut', $filtre))
            ->when($search, fn($q) => $q->whereHas('client', fn($q2) =>
                $q2->where('prenom', 'like', "%{$search}%")
                   ->orWhere('nom', 'like', "%{$search}%")
                   ->orWhere('telephone', 'like', "%{$search}%")
            ))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $totaux = Credit::where('institut_id', $this->institutId())
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN statut = 'en_cours' THEN 1 ELSE 0 END) as en_cours,
                SUM(CASE WHEN statut = 'retard' THEN 1 ELSE 0 END) as en_retard,
                SUM(CASE WHEN statut = 'solde' THEN 1 ELSE 0 END) as soldes,
                COALESCE(SUM(CASE WHEN statut != 'solde' THEN reste_a_payer ELSE 0 END), 0) as total_du
            ")->first();

        return view('dashboard.credits.index', compact('credits', 'filtre', 'totaux'));
    }

    public function show(Credit $credit)
    {
        $credit->load(['client', 'vente.items', 'echeances', 'paiements.encaisseur']);
        return view('dashboard.credits.show', compact('credit'));
    }

    public function create()
    {
        $clients = \App\Models\Client::where('actif', true)->orderBy('prenom')->orderBy('nom')->limit(300)->get();
        $produits = \App\Models\Produit::where('actif', true)->where('stock', '>', 0)->orderBy('nom')->get(['id', 'nom', 'prix_vente']);
        $prestations = \App\Models\Prestation::where('actif', true)->orderBy('nom')->get(['id', 'nom', 'prix']);

        return view('dashboard.credits.create', compact('clients', 'produits', 'prestations'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id'       => ['required', 'uuid', 'exists:clients,id'],
            'articles'        => ['required', 'json'],
            'apport_initial'  => ['required', 'integer', 'min:0'],
            'nb_echeances'    => ['required', 'integer', 'min:1', 'max:24'],
            'frequence'       => ['required', 'in:hebdomadaire,mensuel'],
            'notes'           => ['nullable', 'string', 'max:1000'],
        ]);

        $articles = json_decode($data['articles'], true);
        if (! is_array($articles) || empty($articles)) {
            return back()->withErrors(['articles' => 'Ajoutez au moins un article.']);
        }

        $institutId = $this->institutId();

        $totalBrut = 0;
        $itemsToSave = [];

        foreach ($articles as $art) {
            $nom   = strip_tags(substr((string) ($art['nom'] ?? ''), 0, 150));
            $prix  = max(1, min(10_000_000, (int) ($art['prix'] ?? 0)));
            $qte   = max(1, (int) ($art['quantite'] ?? 1));
            $type  = in_array($art['type'] ?? null, ['produit', 'prestation', 'libre']) ? $art['type'] : 'libre';
            $itemId = $type !== 'libre' ? ($art['item_id'] ?? null) : null;

            abort_if(! $nom || $prix <= 0, 422, 'Article invalide.');

            $sousTotal = $prix * $qte;
            $totalBrut += $sousTotal;

            $itemsToSave[] = [
                'type'          => $type,
                'item_id'       => $itemId,
                'nom_snapshot'  => $nom,
                'prix_snapshot' => $prix,
                'quantite'      => $qte,
                'sous_total'    => $sousTotal,
            ];
        }

        $total  = $totalBrut;
        $apport = $data['apport_initial'];
        $reste  = max(0, $total - $apport);

        $credit = DB::transaction(function () use ($data, $institutId, $total, $apport, $reste, $itemsToSave) {
            $vente = \App\Models\Vente::create([
                'institut_id'   => $institutId,
                'client_id'     => $data['client_id'],
                'user_id'       => Auth::id(),
                'total'         => $total,
                'montant_paye'  => $apport,
                'mode_paiement' => 'credit',
                'credit_statut' => $reste > 0 ? 'en_cours' : 'solde',
                'statut'        => 'validee',
                'ip_address'    => request()->ip(),
            ]);

            foreach ($itemsToSave as $item) {
                $vente->items()->create($item);
            }

            $credit = Credit::create([
                'vente_id'       => $vente->id,
                'institut_id'    => $institutId,
                'client_id'      => $data['client_id'],
                'montant_total'  => $total,
                'apport_initial' => $apport,
                'reste_a_payer'  => $reste,
                'nb_echeances'   => $data['nb_echeances'],
                'frequence'      => $data['frequence'],
                'date_debut'     => now(),
                'date_fin_prevue'=> $this->calculerDateFin(now(), $data['nb_echeances'], $data['frequence']),
                'notes'          => $data['notes'] ?? null,
                'statut'         => $reste > 0 ? 'en_cours' : 'solde',
            ]);

            if ($reste > 0) {
                $parEcheance = (int) round($reste / $data['nb_echeances']);
                $date = now()->copy();
                for ($i = 0; $i < $data['nb_echeances']; $i++) {
                    $date = $data['frequence'] === 'hebdomadaire' ? $date->addWeek() : $date->addMonth();
                    $montant = ($i === $data['nb_echeances'] - 1)
                        ? $reste - ($parEcheance * ($data['nb_echeances'] - 1))
                        : $parEcheance;
                    \App\Models\Echeance::create([
                        'credit_id'   => $credit->id,
                        'institut_id' => $institutId,
                        'numero'      => $i + 1,
                        'date_prevue' => $date->copy(),
                        'montant'     => max(0, $montant),
                        'statut'      => 'en_attente',
                    ]);
                }
            }

            if ($apport > 0) {
                \App\Models\PaiementCredit::create([
                    'credit_id'     => $credit->id,
                    'institut_id'   => $institutId,
                    'montant'       => $apport,
                    'mode_paiement' => 'cash',
                    'encaisse_par'  => Auth::id(),
                    'notes'         => 'Apport initial',
                ]);
            }

            return $credit;
        });

        return redirect()->route('dashboard.credits.show', $credit)
            ->with('success', 'Crédit créé — reste à payer : ' . number_format($reste, 0, ',', ' ') . ' FCFA');
    }

    private function calculerDateFin($date, int $nb, string $freq): \Carbon\Carbon
    {
        $d = $date->copy();
        for ($i = 0; $i < $nb; $i++) {
            $d = $freq === 'hebdomadaire' ? $d->addWeek() : $d->addMonth();
        }
        return $d;
    }

    public function fichePdf(Credit $credit)
    {
        $credit->load(['client', 'vente.items', 'echeances', 'paiements.encaisseur']);
        $institut = Institut::find($this->institutId());

        $pdf = Pdf::loadView('pdf.credit', compact('credit', 'institut'))
            ->setPaper('a4', 'portrait');

        return $pdf->download("fiche-credit-" . substr($credit->id, 0, 8) . ".pdf");
    }

    public function fichePdfPublic(string $id)
    {
        $credit = Credit::where('id', $id)->firstOrFail();
        $credit->load(['client', 'vente.items', 'echeances', 'paiements.encaisseur']);
        $institut = Institut::find($credit->institut_id);

        $pdf = Pdf::loadView('pdf.credit', compact('credit', 'institut'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream("fiche-credit-" . substr($credit->id, 0, 8) . ".pdf");
    }

    public function payer(Request $request, Credit $credit)
    {
        $data = $request->validate([
            'echeance_id'   => ['required', 'uuid', 'exists:echeances,id'],
            'montant'       => ['required', 'integer', 'min:1'],
            'mode_paiement' => ['required', 'in:cash,mobile_money,carte'],
            'reference'     => ['nullable', 'string', 'max:100'],
        ]);

        $echeance = Echeance::findOrFail($data['echeance_id']);
        abort_if($echeance->credit_id !== $credit->id, 403);

        DB::transaction(function () use ($credit, $echeance, $data) {
            PaiementCredit::create([
                'credit_id'     => $credit->id,
                'echeance_id'   => $echeance->id,
                'institut_id'   => $this->institutId(),
                'montant'       => $data['montant'],
                'mode_paiement' => $data['mode_paiement'],
                'reference'     => $data['reference'],
                'encaisse_par'  => Auth::id(),
                'created_at'    => now(),
            ]);

            $echeance->montant_paye += $data['montant'];
            if ($echeance->montant_paye >= $echeance->montant) {
                $echeance->statut = 'payee';
                $echeance->date_paiement = now();
                $echeance->encaisse_par = Auth::id();
            }
            $echeance->save();

            $credit->reste_a_payer = max(0, $credit->reste_a_payer - $data['montant']);
            if ($credit->reste_a_payer <= 0) {
                $credit->statut = 'solde';
                $credit->vente->credit_statut = 'solde';
            } elseif ($credit->statut === 'retard') {
                $aDesRetards = $credit->echeances()
                    ->where('statut', 'retard')
                    ->where('date_prevue', '<', now()->toDateString())
                    ->exists();
                if (! $aDesRetards) {
                    $credit->statut = 'en_cours';
                    $credit->vente->credit_statut = 'en_cours';
                }
            }
            $credit->vente->montant_paye += $data['montant'];
            $credit->vente->save();
            $credit->save();
        });

        return back()->with('success', 'Paiement de ' . number_format($data['montant'], 0, ',', ' ') . ' FCFA enregistré.');
    }
}
