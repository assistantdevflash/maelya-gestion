<?php

namespace App\Http\Controllers\Commercial;

use App\Http\Controllers\Controller;
use App\Models\CommercialActivite;
use App\Models\CommercialCommission;
use App\Models\CommercialProspect;
use App\Models\CommercialProfile;
use App\Services\ProspectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class CommercialController extends Controller
{
    private function getProfil()
    {
        return Auth::user()->commercialProfile;
    }

    private function profilOuEchec(): CommercialProfile
    {
        $profil = $this->getProfil();

        if (! $profil) {
            abort(403, 'Profil commercial introuvable.');
        }

        return $profil;
    }

    /**
     * Récupère un prospect en s'assurant qu'il appartient bien au commercial
     * connecté : sans ce garde-fou, un commercial pourrait modifier les
     * prospects d'un autre en changeant l'identifiant dans l'URL.
     */
    private function prospectAutorise(CommercialProfile $profil, string $id): CommercialProspect
    {
        return $profil->prospects()->whereKey($id)->firstOrFail();
    }

    public function dashboard(ProspectionService $prospection)
    {
        $profil = $this->profilOuEchec();

        $profil->loadCount(['parrainages', 'commissions']);

        $totalGagne     = $profil->totalGagne();
        $totalEnAttente = $profil->totalEnAttente();

        $derniersParrainages = $profil->parrainages()
            ->with('proprietaire.institut')
            ->latest()
            ->limit(5)
            ->get();

        $dernieresCommissions = $profil->commissions()
            ->with('parrainage.proprietaire.institut', 'abonnement.plan')
            ->latest()
            ->limit(5)
            ->get();

        $config = \DB::table('commercial_config')->first();

        // ── Nouveau : le travail du jour ──
        $stats              = $prospection->statistiques($profil);
        $relancesDues       = $prospection->relancesDues($profil)->take(6);
        $echeances          = $prospection->echeancesAbonnements($profil)->take(5);
        $sansRelance        = $prospection->prospectsSansRelance($profil)->take(4);

        return view('commercial.dashboard', compact(
            'profil', 'totalGagne', 'totalEnAttente',
            'derniersParrainages', 'dernieresCommissions', 'config',
            'stats', 'relancesDues', 'echeances', 'sansRelance'
        ));
    }

    public function parrainages()
    {
        $profil = $this->getProfil();
        if (!$profil) abort(403);

        $parrainages = $profil->parrainages()
            ->with('proprietaire.institut', 'commissions')
            ->latest()
            ->paginate(20);

        return view('commercial.parrainages', compact('profil', 'parrainages'));
    }

    public function commissions(Request $request)
    {
        $profil = $this->getProfil();
        if (!$profil) abort(403);

        $query = $profil->commissions()
            ->with('parrainage.proprietaire.institut', 'abonnement.plan')
            ->latest();

        if ($request->statut && in_array($request->statut, ['en_attente', 'payee'])) {
            $query->where('statut', $request->statut);
        }

        $commissions = $query->paginate(30)->withQueryString();

        $totalGagne     = $profil->totalGagne();
        $totalEnAttente = $profil->totalEnAttente();

        return view('commercial.commissions', compact(
            'profil', 'commissions', 'totalGagne', 'totalEnAttente'
        ));
    }

    public function guide()
    {
        return view('commercial.guide');
    }

    public function guidePdf()
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('commercial.guide-pdf')
            ->setPaper('a4', 'portrait');

        return $pdf->download('guide-porte-a-porte-maelya.pdf');
    }

    public function profil()
    {
        $profil = $this->getProfil();
        if (!$profil) abort(403);

        $profil->loadCount(['parrainages', 'commissions']);
        $totalGagne     = $profil->totalGagne();
        $totalEnAttente = $profil->totalEnAttente();
        $config         = \DB::table('commercial_config')->first();

        return view('commercial.profil', compact(
            'profil', 'totalGagne', 'totalEnAttente', 'config'
        ));
    }

    public function updateProfil(Request $request)
    {
        $user   = Auth::user();
        $profil = $user->commercialProfile;

        $validated = $request->validate([
            'prenom'      => 'required|string|max:100',
            'nom_famille' => 'required|string|max:100',
            'email'       => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'telephone'   => 'nullable|string|max:30',
        ]);

        $user->update([
            'prenom'      => $validated['prenom'],
            'nom_famille' => $validated['nom_famille'],
            'name'        => $validated['prenom'] . ' ' . $validated['nom_famille'],
            'email'       => $validated['email'],
            'telephone'   => $validated['telephone'],
        ]);

        if ($profil) {
            $profil->update(['telephone' => $validated['telephone']]);
        }

        return back()->with('success', 'Profil mis à jour avec succès.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|min:8|confirmed',
        ]);

        if (!Hash::check($request->current_password, Auth::user()->password)) {
            return back()
                ->withErrors(['current_password' => 'Le mot de passe actuel est incorrect.'])
                ->withInput()
                ->with('tab', 'password');
        }

        Auth::user()->update(['password' => Hash::make($request->password)]);

        return back()->with('success_password', 'Mot de passe modifié avec succès.');
    }

    // ═════════════════════════════════════════════════════════════════════
    //  PROSPECTION
    // ═════════════════════════════════════════════════════════════════════

    /**
     * Pipeline de prospection : liste filtrable des prospects.
     */
    public function prospection(Request $request, ProspectionService $prospection)
    {
        $profil = $this->profilOuEchec();

        $statut   = $request->input('statut');
        $recherche = trim((string) $request->input('q'));

        $query = $profil->prospects()
            ->with('activites')
            ->withCount('activites')
            ->latest('updated_at');

        if ($statut === 'actifs') {
            $query->enCours();
        } elseif ($statut && array_key_exists($statut, CommercialProspect::STATUTS)) {
            $query->where('statut', $statut);
        }

        if ($recherche !== '') {
            $like = '%' . $recherche . '%';
            $query->where(function ($q) use ($like) {
                $q->where('nom_etablissement', 'like', $like)
                  ->orWhere('contact_nom', 'like', $like)
                  ->orWhere('contact_telephone', 'like', $like)
                  ->orWhere('contact_email', 'like', $like)
                  ->orWhere('ville', 'like', $like)
                  ->orWhere('quartier', 'like', $like);
            });
        }

        $prospects = $query->paginate(25)->withQueryString();

        $stats       = $prospection->statistiques($profil);
        $aRelancer   = $prospection->relancesDues($profil);
        $sansRelance = $prospection->prospectsSansRelance($profil);

        $typesActivite = CommercialProspect::typesActivite();

        return view('commercial.prospection', compact(
            'profil', 'prospects', 'stats', 'aRelancer', 'sansRelance', 'statut', 'recherche',
            'typesActivite'
        ));
    }

    /**
     * Page « Relances » : la file de travail du jour et les échéances
     * d'abonnement des filleuls.
     */
    public function relances(ProspectionService $prospection)
    {
        $profil = $this->profilOuEchec();

        $relancesDues   = $prospection->relancesDues($profil);
        $relancesAVenir = $prospection->relancesAVenir($profil);
        $sansRelance    = $prospection->prospectsSansRelance($profil);
        $echeances      = $prospection->echeancesAbonnements($profil, 45);
        $stats          = $prospection->statistiques($profil);

        return view('commercial.relances', compact(
            'profil', 'relancesDues', 'relancesAVenir', 'sansRelance', 'echeances', 'stats'
        ));
    }

    public function storeProspect(Request $request)
    {
        $profil = $this->profilOuEchec();

        $data = $request->validate([
            'nom_etablissement'  => ['required', 'string', 'max:150'],
            'type_activite'      => ['nullable', Rule::exists('etablissement_types', 'code')->where('actif', true)],
            'ville'              => ['nullable', 'string', 'max:80'],
            'quartier'           => ['nullable', 'string', 'max:80'],
            'contact_nom'        => ['nullable', 'string', 'max:120'],
            'contact_telephone'  => ['nullable', 'string', 'max:30'],
            'contact_email'      => ['nullable', 'email', 'max:150'],
            'statut'             => ['nullable', Rule::in(array_keys(CommercialProspect::STATUTS))],
            'source'             => ['nullable', Rule::in(array_keys(CommercialProspect::SOURCES))],
            'priorite'           => ['nullable', Rule::in(array_keys(CommercialProspect::PRIORITES))],
            'prochaine_relance_le' => ['nullable', 'date'],
            'notes'              => ['nullable', 'string', 'max:2000'],
        ]);

        $data['commercial_id'] = $profil->id;
        $data['statut']        = $data['statut'] ?? 'a_contacter';
        $data['source']        = $data['source'] ?? 'porte_a_porte';
        $data['priorite']      = $data['priorite'] ?? 'normale';

        $prospect = CommercialProspect::create($data);

        CommercialActivite::create([
            'prospect_id'   => $prospect->id,
            'commercial_id' => $profil->id,
            'type'          => 'note',
            'contenu'       => 'Prospect ajouté au pipeline.',
            'fait_le'       => today(),
        ]);

        return redirect()
            ->route('commercial.prospection', ['statut' => $prospect->statut])
            ->with('success', $prospect->nom_etablissement . ' ajouté à votre pipeline.');
    }

    public function updateProspect(Request $request, string $prospect)
    {
        $profil   = $this->profilOuEchec();
        $prospect = $this->prospectAutorise($profil, $prospect);

        $data = $request->validate([
            'nom_etablissement'  => ['required', 'string', 'max:150'],
            'type_activite'      => ['nullable', Rule::exists('etablissement_types', 'code')->where('actif', true)],
            'ville'              => ['nullable', 'string', 'max:80'],
            'quartier'           => ['nullable', 'string', 'max:80'],
            'contact_nom'        => ['nullable', 'string', 'max:120'],
            'contact_telephone'  => ['nullable', 'string', 'max:30'],
            'contact_email'      => ['nullable', 'email', 'max:150'],
            'source'             => ['nullable', Rule::in(array_keys(CommercialProspect::SOURCES))],
            'priorite'           => ['nullable', Rule::in(array_keys(CommercialProspect::PRIORITES))],
            'prochaine_relance_le' => ['nullable', 'date'],
            'notes'              => ['nullable', 'string', 'max:2000'],
        ]);

        $prospect->update($data);

        return back()->with('success', 'Fiche prospect mise à jour.');
    }

    /**
     * Changement de statut dans le pipeline (bouton rapide ou menu déroulant).
     */
    public function changerStatutProspect(Request $request, string $prospect)
    {
        $profil   = $this->profilOuEchec();
        $prospect = $this->prospectAutorise($profil, $prospect);

        $data = $request->validate([
            'statut' => ['required', Rule::in(array_keys(CommercialProspect::STATUTS))],
        ]);

        $ancien = $prospect->statut;

        $prospect->statut = $data['statut'];

        if ($data['statut'] === 'converti' && $prospect->converti_le === null) {
            $prospect->converti_le = now();
            // Un prospect converti n'a plus besoin d'être relancé.
            $prospect->prochaine_relance_le = null;
        }

        if (in_array($data['statut'], ['converti', 'perdu'], true)) {
            $prospect->prochaine_relance_le = null;
        }

        $prospect->save();

        if ($ancien !== $data['statut']) {
            CommercialActivite::create([
                'prospect_id'   => $prospect->id,
                'commercial_id' => $profil->id,
                'type'          => 'note',
                'contenu'       => 'Statut : ' . (CommercialProspect::STATUTS[$ancien] ?? $ancien)
                    . ' → ' . (CommercialProspect::STATUTS[$data['statut']] ?? $data['statut']),
                'fait_le'       => today(),
            ]);
        }

        return back()->with('success', 'Statut mis à jour : ' . $prospect->libelleStatut() . '.');
    }

    /**
     * Planifie (ou décale) la prochaine relance.
     */
    public function planifierRelance(Request $request, string $prospect)
    {
        $profil   = $this->profilOuEchec();
        $prospect = $this->prospectAutorise($profil, $prospect);

        $data = $request->validate([
            'prochaine_relance_le' => ['required', 'date'],
            'contenu'              => ['nullable', 'string', 'max:500'],
        ]);

        $prospect->update([
            'prochaine_relance_le' => $data['prochaine_relance_le'],
        ]);

        CommercialActivite::create([
            'prospect_id'   => $prospect->id,
            'commercial_id' => $profil->id,
            'type'          => 'relance',
            'contenu'       => ($data['contenu'] ?? null)
                ?: 'Relance planifiée pour le ' . \Carbon\Carbon::parse($data['prochaine_relance_le'])->format('d/m/Y') . '.',
            'fait_le'       => today(),
        ]);

        return back()->with('success', 'Relance planifiée pour le '
            . \Carbon\Carbon::parse($data['prochaine_relance_le'])->format('d/m/Y') . '.');
    }

    /**
     * Enregistre une interaction et fait avancer la fiche.
     */
    public function ajouterActivite(Request $request, string $prospect)
    {
        $profil   = $this->profilOuEchec();
        $prospect = $this->prospectAutorise($profil, $prospect);

        $data = $request->validate([
            'type'    => ['required', Rule::in(array_keys(CommercialActivite::TYPES))],
            'contenu' => ['required', 'string', 'max:1000'],
            'fait_le' => ['nullable', 'date'],
            'prochaine_relance_le' => ['nullable', 'date'],
        ]);

        CommercialActivite::create([
            'prospect_id'   => $prospect->id,
            'commercial_id' => $profil->id,
            'type'          => $data['type'],
            'contenu'       => $data['contenu'],
            'fait_le'       => $data['fait_le'] ?? today(),
        ]);

        $prospect->derniere_interaction_le = $data['fait_le'] ?? today();

        if (! empty($data['prochaine_relance_le'])) {
            $prospect->prochaine_relance_le = $data['prochaine_relance_le'];
        }

        $prospect->save();

        return back()->with('success', 'Interaction enregistrée.');
    }

    public function destroyProspect(string $prospect)
    {
        $profil   = $this->profilOuEchec();
        $prospect = $this->prospectAutorise($profil, $prospect);

        $nom = $prospect->nom_etablissement;
        $prospect->delete();

        return redirect()
            ->route('commercial.prospection')
            ->with('success', $nom . ' a été supprimé de votre pipeline.');
    }
}
