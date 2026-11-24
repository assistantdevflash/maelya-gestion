<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Avoir;
use App\Models\Client;
use App\Models\CodeReduction;
use App\Models\CodeReductionUtilisation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Écran « Remises & Avoirs » : codes de réduction et avoirs clients.
 */
class CodeReductionController extends Controller
{
    /** Alphabet sans caractères ambigus (I/1, O/0), lisible sur un ticket. */
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private function institutId(): string
    {
        return session('current_institut_id', Auth::user()->institut_id);
    }

    public function index(Request $request)
    {
        $institutId   = $this->institutId();
        $search       = trim((string) $request->input('q'));
        $filtreStatut = (string) $request->input('statut');
        $filtreType   = (string) $request->input('type');
        $tri          = (string) $request->input('tri', 'recent');
        $sens         = $request->input('dir') === 'asc' ? 'asc' : 'desc';

        $aujourdhui = now()->toDateString();
        $maintenant = now()->toDateTimeString();

        $codes = CodeReduction::where('institut_id', $institutId)
            ->with('client')
            ->when($search, function (Builder $q) use ($search) {
                $q->where(function (Builder $q2) use ($search) {
                    $q2->where('code', 'like', "%{$search}%")
                       ->orWhere('description', 'like', "%{$search}%")
                       ->orWhereHas('client', fn (Builder $q3) => $q3
                           ->where('prenom', 'like', "%{$search}%")
                           ->orWhere('nom', 'like', "%{$search}%"));
                });
            })
            ->when($filtreType === 'pourcentage' || $filtreType === 'montant_fixe',
                fn (Builder $q) => $q->where('type', $filtreType))
            ->when($filtreStatut !== '', fn (Builder $q) => $this->filtrerParStatut($q, $filtreStatut, $aujourdhui, $maintenant));

        match ($tri) {
            'code'        => $codes->orderBy('code', $sens),
            'valeur'      => $codes->orderBy('valeur', $sens),
            'utilisation' => $codes->orderBy('nb_utilisations', $sens),
            'expiration'  => $codes->orderBy('date_fin', $sens),
            default       => $codes->orderByDesc('created_at'),
        };

        $codes = $codes->paginate(25)->withQueryString();

        $clients = Client::where('institut_id', $institutId)
            ->where('actif', true)
            ->orderBy('prenom')
            ->limit(300)
            ->get();

        // Stats calculées en base (indépendantes de la pagination)
        //
        // Les bornes sont passées en paramètres plutôt qu'écrites en dur :
        // `NOW()` n'existe pas en SQLite, où la page tombait en erreur 500.
        $stats = CodeReduction::where('institut_id', $institutId)
            ->selectRaw("
                COUNT(*) as total,
                COALESCE(SUM(CASE WHEN actif = 1
                    AND (limite_utilisation IS NULL OR nb_utilisations < limite_utilisation)
                    AND (date_fin IS NULL OR date_fin >= ?)
                    AND (date_debut IS NULL OR date_debut <= ?)
                    THEN 1 ELSE 0 END), 0) as actifs,
                COALESCE(SUM(nb_utilisations), 0) as utilisations
            ", [$aujourdhui, $maintenant])
            ->first()
            ->toArray();

        $stats['total']        = (int) $stats['total'];
        $stats['actifs']       = (int) $stats['actifs'];
        $stats['utilisations'] = (int) $stats['utilisations'];

        // Remise réellement accordée, d'après l'historique.
        $stats['remise_totale'] = (int) CodeReductionUtilisation::where('institut_id', $institutId)
            ->sum('montant_remise');

        $stats['remise_30_jours'] = (int) CodeReductionUtilisation::where('institut_id', $institutId)
            ->where('created_at', '>=', now()->subDays(30)->startOfDay())
            ->sum('montant_remise');

        // Répartition par statut, pour les pastilles de filtre.
        $repartition = ['actif' => 0, 'programme' => 0, 'epuise' => 0, 'expire' => 0, 'inactif' => 0];

        foreach (CodeReduction::where('institut_id', $institutId)->get() as $unCode) {
            $repartition[$unCode->statut()] = ($repartition[$unCode->statut()] ?? 0) + 1;
        }

        $avoirs = Avoir::where('institut_id', $institutId)
            ->with(['vente', 'client', 'codeReduction'])
            ->when($search, function (Builder $q) use ($search) {
                $q->where(function (Builder $q2) use ($search) {
                    $q2->where('numero', 'like', "%{$search}%")
                       ->orWhere('motif', 'like', "%{$search}%")
                       ->orWhereHas('client', fn (Builder $q3) => $q3
                           ->where('prenom', 'like', "%{$search}%")
                           ->orWhere('nom', 'like', "%{$search}%"))
                       ->orWhereHas('codeReduction', fn (Builder $q3) => $q3
                           ->where('code', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('dashboard.codes-reduction.index', [
            'codes'        => $codes,
            'stats'        => $stats,
            'clients'      => $clients,
            'avoirs'       => $avoirs,
            // Permet d'ouvrir directement l'onglet demandé par un lien.
            'onglet'       => $request->input('tab') === 'avoirs' ? 'avoirs' : 'codes',
            'repartition'  => $repartition,
            'sparkline'    => $this->sparkline($institutId),
            'filtreStatut' => $filtreStatut,
            'filtreType'   => $filtreType,
            'tri'          => $tri,
            'sens'         => $sens,
        ]);
    }

    /**
     * Applique le filtre de statut, en respectant la priorité d'affichage :
     * inactif → épuisé → expiré → programmé → actif.
     */
    private function filtrerParStatut(Builder $query, string $statut, string $aujourdhui, string $maintenant): Builder
    {
        $nonEpuise = fn (Builder $q) => $q->whereNull('limite_utilisation')
            ->orWhereColumn('nb_utilisations', '<', 'limite_utilisation');

        return match ($statut) {
            'inactif'   => $query->where('actif', false),
            'epuise'    => $query->where('actif', true)
                ->whereNotNull('limite_utilisation')
                ->whereColumn('nb_utilisations', '>=', 'limite_utilisation'),
            'expire'    => $query->where('actif', true)
                ->where($nonEpuise)
                ->whereNotNull('date_fin')->where('date_fin', '<', $aujourdhui),
            'programme' => $query->where('actif', true)
                ->where($nonEpuise)
                ->where(fn (Builder $q) => $q->whereNull('date_fin')->orWhere('date_fin', '>=', $aujourdhui))
                ->whereNotNull('date_debut')->where('date_debut', '>', $maintenant),
            'actif'     => $query->where('actif', true)
                ->where($nonEpuise)
                ->where(fn (Builder $q) => $q->whereNull('date_fin')->orWhere('date_fin', '>=', $aujourdhui))
                ->where(fn (Builder $q) => $q->whereNull('date_debut')->orWhere('date_debut', '<=', $maintenant)),
            default     => $query,
        };
    }

    /**
     * Nombre d'utilisations par jour sur les 30 derniers jours.
     */
    private function sparkline(string $institutId): array
    {
        $debut = now()->subDays(29)->startOfDay();

        $parJour = CodeReductionUtilisation::where('institut_id', $institutId)
            ->where('created_at', '>=', $debut)
            ->get(['created_at'])
            ->groupBy(fn (CodeReductionUtilisation $u) => $u->created_at->toDateString())
            ->map->count();

        return collect(range(29, 0))
            ->map(fn (int $i) => (int) ($parJour[now()->subDays($i)->toDateString()] ?? 0))
            ->all();
    }

    public function store(Request $request)
    {
        $data = $this->validerRegles($request);

        $codeUp = strtoupper($data['code']);
        $exists = CodeReduction::where('institut_id', $this->institutId())
            ->where('code', $codeUp)
            ->exists();

        if ($exists) {
            return back()->withErrors(['code' => 'Ce code existe déjà pour votre institut.'])->withInput();
        }

        CodeReduction::create([...$data, 'code' => $codeUp, 'institut_id' => $this->institutId()]);

        return redirect()
            ->route('dashboard.codes-reduction.index')
            ->with('success', 'Code de réduction créé.');
    }

    /**
     * Modifier un code existant : le compteur d'utilisations est conservé.
     */
    public function update(Request $request, CodeReduction $codeReduction)
    {
        abort_unless($codeReduction->institut_id === $this->institutId(), 403);

        $data = $this->validerRegles($request);

        $codeUp = strtoupper($data['code']);
        $exists = CodeReduction::where('institut_id', $this->institutId())
            ->where('code', $codeUp)
            ->whereKeyNot($codeReduction->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['code' => 'Ce code existe déjà pour votre institut.'])->withInput();
        }

        $codeReduction->update([...$data, 'code' => $codeUp]);

        return redirect()
            ->route('dashboard.codes-reduction.index', ['q' => $codeUp])
            ->with('success', 'Code de réduction modifié.');
    }

    /**
     * Dupliquer un code : nouvelle référence, compteur remis à zéro.
     */
    public function dupliquer(CodeReduction $codeReduction)
    {
        abort_unless($codeReduction->institut_id === $this->institutId(), 403);

        $copie = $codeReduction->replicate(['nb_utilisations']);
        $copie->code            = $this->codeDisponible($codeReduction->code . '-COPIE');
        $copie->nb_utilisations = 0;
        $copie->actif           = true;
        $copie->save();

        return redirect()
            ->route('dashboard.codes-reduction.index', ['q' => $copie->code])
            ->with('success', 'Code dupliqué : ' . $copie->code);
    }

    /**
     * Générer plusieurs codes d'un coup, pour une opération marketing.
     */
    public function generer(Request $request)
    {
        $data = $request->validate([
            'quantite'           => ['required', 'integer', 'min:1', 'max:200'],
            'prefixe'            => ['nullable', 'string', 'max:12', 'regex:/^[A-Za-z0-9]*$/'],
            'type'               => ['required', 'in:pourcentage,montant_fixe'],
            'valeur'             => ['required', 'integer', 'min:1'],
            'plafond_remise'     => ['nullable', 'integer', 'min:1'],
            'montant_minimum'    => ['nullable', 'integer', 'min:0'],
            'date_fin'           => ['nullable', 'date', 'after_or_equal:today'],
            'limite_utilisation' => ['required', 'integer', 'min:1', 'max:100'],
            'description'        => ['nullable', 'string', 'max:255'],
        ], [
            'prefixe.regex'           => 'Le préfixe ne peut contenir que des lettres et des chiffres.',
            'quantite.max'            => '200 codes au maximum par génération.',
            'date_fin.after_or_equal' => 'La date de fin doit être aujourd\'hui ou plus tard.',
        ]);

        if ($data['type'] === 'pourcentage' && $data['valeur'] > 100) {
            return back()->withErrors(['valeur' => 'Un pourcentage ne peut pas dépasser 100%.'])->withInput();
        }

        $prefixe    = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $data['prefixe'] ?? '') ?: 'PROMO');
        $institutId = $this->institutId();

        // Le préfixe FID- est réservé aux codes générés par le programme de
        // fidélité : sans ce garde-fou, un code manuel serait compté comme
        // une récompense dans la fiche client.
        if (str_starts_with($prefixe, rtrim(\App\Services\FideliteService::PREFIXE_CODE, '-'))) {
            return back()->withErrors(['prefixe' => 'Le préfixe FID est réservé aux récompenses de fidélité.'])->withInput();
        }

        DB::transaction(function () use ($data, $prefixe, $institutId) {
            for ($i = 0; $i < $data['quantite']; $i++) {
                CodeReduction::create([
                    'institut_id'        => $institutId,
                    'code'               => $this->codeDisponible($prefixe . '-' . $this->suffixeAleatoire()),
                    'description'        => $data['description'] ?? null,
                    'type'               => $data['type'],
                    'valeur'             => $data['valeur'],
                    'plafond_remise'     => $data['type'] === 'pourcentage' ? ($data['plafond_remise'] ?? null) : null,
                    'montant_minimum'    => $data['montant_minimum'] ?? null,
                    'date_debut'         => now()->toDateString(),
                    'date_fin'           => $data['date_fin'] ?? null,
                    'limite_utilisation' => $data['limite_utilisation'],
                    'nb_utilisations'    => 0,
                    'actif'              => true,
                ]);
            }
        });

        return redirect()
            ->route('dashboard.codes-reduction.index', ['q' => $prefixe])
            ->with('success', \App\Helpers\TexteHelper::pluriel((int) $data['quantite'], 'code généré', 'codes générés')
                . ' avec le préfixe ' . $prefixe . '.');
    }

    /**
     * Historique d'utilisation d'un code (JSON, pour le panneau latéral).
     */
    public function utilisations(CodeReduction $codeReduction)
    {
        abort_unless($codeReduction->institut_id === $this->institutId(), 403);

        $lignes = $codeReduction->utilisations()
            ->with(['client', 'vente'])
            ->limit(50)
            ->get()
            ->map(fn (CodeReductionUtilisation $u) => [
                'date'   => $u->created_at?->format('d/m/Y H:i'),
                'client' => $u->client ? $u->client->prenom . ' ' . $u->client->nom : 'Client de passage',
                'vente'  => $u->vente?->numero ?? ($u->vente ? '#' . substr($u->vente->id, 0, 8) : '—'),
                'remise' => number_format($u->montant_remise, 0, ',', ' ') . ' F',
            ]);

        return response()->json([
            'code'   => $codeReduction->code,
            'total'  => $codeReduction->nb_utilisations,
            'lignes' => $lignes,
        ]);
    }

    /**
     * Export CSV des codes de réduction.
     */
    public function export()
    {
        $codes = CodeReduction::where('institut_id', $this->institutId())
            ->with('client')
            ->orderBy('code')
            ->get();

        return response()->streamDownload(function () use ($codes) {
            $sortie = fopen('php://output', 'w');
            fputcsv($sortie, ['Code', 'Description', 'Type', 'Valeur', 'Plafond', 'Portée', 'Minimum', 'Début', 'Fin', 'Limite', 'Utilisations', 'Statut'], ';');

            foreach ($codes as $code) {
                fputcsv($sortie, [
                    $code->code,
                    $code->description,
                    $code->type,
                    $code->valeur,
                    $code->plafond_remise ?? '',
                    $code->client ? $code->client->prenom . ' ' . $code->client->nom : 'Tous les clients',
                    $code->montant_minimum ?? '',
                    $code->date_debut?->toDateString() ?? '',
                    $code->date_fin?->toDateString() ?? '',
                    $code->limite_utilisation ?? 'illimité',
                    $code->nb_utilisations,
                    $code->statut(),
                ], ';');
            }

            fclose($sortie);
        }, 'codes-reduction-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function print(CodeReduction $codeReduction)
    {
        abort_unless($codeReduction->institut_id === $this->institutId(), 403);

        return view('dashboard.codes-reduction.print', [
            'code' => $codeReduction->load('client', 'institut'),
        ]);
    }

    public function toggle(CodeReduction $codeReduction)
    {
        // S'assurer que le code appartient à l'institut
        abort_unless($codeReduction->institut_id === $this->institutId(), 403);

        $codeReduction->update(['actif' => !$codeReduction->actif]);

        return back()->with('success', $codeReduction->actif ? 'Code activé.' : 'Code désactivé.');
    }

    public function destroy(CodeReduction $codeReduction)
    {
        abort_unless($codeReduction->institut_id === $this->institutId(), 403);

        $codeReduction->delete();

        return back()->with('success', 'Code supprimé.');
    }

    /**
     * Validation AJAX/Livewire — appelée depuis la caisse pour vérifier un code.
     */
    public function valider(Request $request)
    {
        $request->validate([
            'code'      => ['required', 'string'],
            'total'     => ['required', 'integer', 'min:0'],
            'client_id' => ['nullable', 'string'],
        ]);

        $code = CodeReduction::where('institut_id', $this->institutId())
            ->where('code', strtoupper($request->code))
            ->first();

        if (!$code) {
            return response()->json(['error' => 'Code de réduction invalide.'], 422);
        }

        $erreur = $code->validerPourTotal((int) $request->total, $request->client_id ?: null);
        if ($erreur) {
            return response()->json(['error' => $erreur], 422);
        }

        $remise = $code->calculerRemise((int) $request->total);

        return response()->json([
            'id'          => $code->id,
            'code'        => $code->code,
            'type'        => $code->type,
            'valeur'      => $code->valeur,
            'remise'      => $remise,
            'description' => $code->description,
        ]);
    }

    /**
     * Règles de validation communes à la création et à la modification.
     */
    private function validerRegles(Request $request): array
    {
        $data = $request->validate([
            'code'              => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9_\-]+$/', function (string $attribut, $valeur, \Closure $fail) {
                // FID- est réservé aux codes générés par le programme de fidélité.
                if (str_starts_with(strtoupper((string) $valeur), \App\Services\FideliteService::PREFIXE_CODE)) {
                    $fail('Le préfixe FID- est réservé aux récompenses de fidélité.');
                }
            }],
            'description'       => ['nullable', 'string', 'max:255'],
            'type'              => ['required', 'in:pourcentage,montant_fixe'],
            'valeur'            => ['required', 'integer', 'min:1'],
            'plafond_remise'    => ['nullable', 'integer', 'min:1'],
            'client_id'         => ['nullable', 'string', 'exists:clients,id'],
            'montant_minimum'   => ['nullable', 'integer', 'min:0'],
            'date_debut'        => ['nullable', 'date'],
            'date_fin'          => ['nullable', 'date', 'after_or_equal:date_debut'],
            'limite_utilisation'=> ['nullable', 'integer', 'min:1'],
        ], [
            'code.regex'     => 'Le code ne peut contenir que des lettres majuscules, chiffres, tirets et underscores.',
            'valeur.min'     => 'La valeur doit être au moins 1.',
            'type.in'        => 'Type invalide.',
            'date_fin.after_or_equal' => 'La date de fin doit être après la date de début.',
        ]);

        if ($data['type'] === 'pourcentage' && $data['valeur'] > 100) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'valeur' => 'Un pourcentage ne peut pas dépasser 100%.',
            ]);
        }

        // Le plafond n'a de sens que sur un pourcentage.
        if ($data['type'] === 'montant_fixe') {
            $data['plafond_remise'] = null;
        }

        return $data;
    }

    /**
     * Code libre pour cet institut, en suffixant si nécessaire.
     */
    private function codeDisponible(string $base): string
    {
        $base = strtoupper(preg_replace('/[^A-Z0-9_\-]/i', '', $base) ?: 'CODE');
        $base = substr($base, 0, 50);
        $code = $base;
        $suffixe = 2;

        while (CodeReduction::where('institut_id', $this->institutId())->where('code', $code)->exists()) {
            $code = substr($base, 0, 45) . '-' . $suffixe;
            $suffixe++;
        }

        return $code;
    }

    private function suffixeAleatoire(int $longueur = 6): string
    {
        $suffixe = '';

        for ($i = 0; $i < $longueur; $i++) {
            $suffixe .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        return $suffixe;
    }
}
