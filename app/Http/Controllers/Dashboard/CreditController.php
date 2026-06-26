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
