<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\CodeReduction;
use App\Models\Prestation;
use App\Models\Produit;
use App\Models\RendezVous;
use App\Models\Vente;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $q = trim($request->input('q', ''));
        abort_if(strlen($q) < 2, 422, 'Recherche trop courte (min 2 caractères).');

        $institutId = session('current_institut_id', auth()->user()->institut_id);
        $limit = 5; // max résultats par catégorie

        // ── Clients ──
        $clients = Client::where('institut_id', $institutId)
            ->where('actif', true)
            ->where(function ($sql) use ($q) {
                $sql->where('prenom', 'like', "%{$q}%")
                    ->orWhere('nom', 'like', "%{$q}%")
                    ->orWhere('telephone', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            })
            ->limit($limit)
            ->get(['id', 'prenom', 'nom', 'telephone', 'email'])
            ->map(fn ($c) => [
                'id'         => $c->id,
                'label'      => $c->nom_complet,
                'sous_label' => $c->telephone ?: $c->email ?: '—',
                'url'        => route('dashboard.clients.show', $c),
                'icone'      => 'client',
            ]);

        // ── Ventes ──
        $ventes = Vente::where('institut_id', $institutId)
            ->where(function ($sql) use ($q) {
                $sql->where('numero', 'like', "%{$q}%")
                    ->orWhere('numero_facture', 'like', "%{$q}%");
            })
            ->with('client:id,prenom,nom')
            ->latest()
            ->limit($limit)
            ->get(['id', 'numero', 'numero_facture', 'total', 'client_id', 'created_at'])
            ->map(fn ($v) => [
                'id'         => $v->id,
                'label'      => $v->numero_facture ?: 'Vente #' . $v->numero,
                'sous_label' => $v->client?->nom_complet ?? 'Client inconnu',
                'url'        => route('dashboard.ventes.show', $v),
                'icone'      => 'vente',
            ]);

        // ── Rendez-vous ──
        $rdvs = RendezVous::where('institut_id', $institutId)
            ->where(function ($sql) use ($q) {
                $sql->whereHas('client', function ($q2) use ($q) {
                    $q2->where('prenom', 'like', "%{$q}%")
                       ->orWhere('nom', 'like', "%{$q}%");
                });
            })
            ->with('client:id,prenom,nom')
            ->latest('debut_le')
            ->limit($limit)
            ->get(['id', 'client_id', 'debut_le', 'statut'])
            ->map(fn ($r) => [
                'id'         => $r->id,
                'label'      => $r->client?->nom_complet ?? 'Inconnu',
                'sous_label' => $r->debut_le?->translatedFormat('d/m/Y H:i') . ' — ' . ($r->statut_badge['label'] ?? $r->statut),
                'url'        => route('dashboard.rdv.show', $r),
                'icone'      => 'rdv',
            ]);

        // ── Prestations ──
        $prestations = Prestation::where('institut_id', $institutId)
            ->where('actif', true)
            ->where('nom', 'like', "%{$q}%")
            ->limit($limit)
            ->get(['id', 'nom', 'prix'])
            ->map(fn ($p) => [
                'id'         => $p->id,
                'label'      => $p->nom,
                'sous_label' => number_format($p->prix, 0, ',', ' ') . ' FCFA',
                'url'        => route('dashboard.prestations.index', ['q' => $p->nom]),
                'icone'      => 'prestation',
            ]);

        // ── Produits ──
        $produits = Produit::where('institut_id', $institutId)
            ->where('actif', true)
            ->where(function ($sql) use ($q) {
                $sql->where('nom', 'like', "%{$q}%")
                    ->orWhere('reference', 'like', "%{$q}%")
                    ->orWhere('code_barre', 'like', "%{$q}%");
            })
            ->limit($limit)
            ->get(['id', 'nom', 'prix_vente', 'stock', 'reference'])
            ->map(fn ($p) => [
                'id'         => $p->id,
                'label'      => $p->nom,
                'sous_label' => ($p->reference ? 'Réf: ' . $p->reference . ' — ' : '') . number_format($p->prix_vente, 0, ',', ' ') . ' FCFA',
                'url'        => route('dashboard.produits.index', ['q' => $p->nom]),
                'icone'      => 'produit',
            ]);

        // ── Codes de réduction ──
        $codes = CodeReduction::where('institut_id', $institutId)
            ->where('code', 'like', "%{$q}%")
            ->limit($limit)
            ->get(['id', 'code', 'type', 'valeur', 'description'])
            ->map(fn ($c) => [
                'id'         => $c->id,
                'label'      => $c->code,
                'sous_label' => $c->description ?? ($c->type === 'pourcentage' ? $c->valeur . '%' : number_format($c->valeur, 0, ',', ' ') . ' FCFA'),
                'url'        => route('dashboard.codes-reduction.index'),
                'icone'      => 'code',
            ]);

        $groupes = collect([
            ['cle' => 'clients',    'titre' => 'Clients',         'icone' => 'client',     'resultats' => $clients],
            ['cle' => 'ventes',     'titre' => 'Ventes',          'icone' => 'vente',      'resultats' => $ventes],
            ['cle' => 'rdvs',       'titre' => 'Rendez-vous',     'icone' => 'rdv',        'resultats' => $rdvs],
            ['cle' => 'prestations', 'titre' => 'Prestations',    'icone' => 'prestation', 'resultats' => $prestations],
            ['cle' => 'produits',   'titre' => 'Produits',        'icone' => 'produit',    'resultats' => $produits],
            ['cle' => 'codes',      'titre' => 'Codes promo',     'icone' => 'code',       'resultats' => $codes],
        ])->filter(fn ($g) => $g['resultats']->isNotEmpty())->values();

        return response()->json([
            'q'       => $q,
            'groupes' => $groupes,
            'total'   => $groupes->sum(fn ($g) => $g['resultats']->count()),
        ]);
    }
}
