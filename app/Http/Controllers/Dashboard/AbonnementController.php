<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Mail\NouvelleDemandeAbonnement;
use App\Models\Abonnement;
use App\Models\Institut;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Models\PlanAbonnement;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\PaymentGatewayManager;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AbonnementController extends Controller
{
    public function expire()
    {
        return view('dashboard.abonnement.expire');
    }

    /**
     * Page d'invitation à passer au plan supérieur lorsqu'une fonctionnalité
     * n'est pas disponible dans le plan actuel.
     */
    public function upgrade(Request $request)
    {
        $feature = (string) $request->query('feature', '');
        $meta = config("plans-features.meta.$feature");

        // Si la feature n'existe pas dans la matrice, on tombe sur la liste des plans
        if (!$meta) {
            return redirect()->route('abonnement.plans');
        }

        $planRequis = PlanAbonnement::where('slug', $meta['plan_requis'])
            ->where('actif', true)
            ->first();

        $abonnementActif = Auth::user()->abonnementActif;

        return view('dashboard.abonnement.upgrade', compact(
            'feature', 'meta', 'planRequis', 'abonnementActif'
        ));
    }

    public function historique()
    {
        $user = Auth::user();

        // 1. Abonnements : souscriptions ET demandes d'option boutique par virement
        $abonnements = Abonnement::where('user_id', $user->id)
            ->with('plan', 'validePar')
            ->orderByDesc('created_at')
            ->get();

        // 2. Paiements en ligne (GeniusPay)
        $paiements = PaymentTransaction::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        // Transactions GeniusPay rattachées à un abonnement (souscription /
        // renouvellement / upgrade) → on les fusionne avec l'abonnement concerné
        // pour éviter les doublons.
        $paiementsParAbonnement = $paiements
            ->filter(fn ($tx) => in_array($tx->type, ['abonnement', 'renouvellement', 'upgrade'], true) && $tx->abonnement_id)
            ->keyBy('abonnement_id');

        $items = collect();

        foreach ($abonnements as $abo) {
            $items->push($this->transactionDepuisAbonnement($abo, $paiementsParAbonnement->get($abo->id)));
        }

        // 3. Paiements boutique en ligne INDIVIDUELS (GeniusPay) : ils n'ont pas
        // d'enregistrement `Abonnement` dédié, on les ajoute donc séparément.
        foreach ($paiements->whereIn('type', ['boutique_activation', 'boutique_renouvellement']) as $tx) {
            $items->push($this->transactionDepuisPaiement($tx));
        }

        // Tri par date décroissante (abonnements + paiements mélangés)
        $items = $items->sortByDesc(fn ($i) => $i['date']->timestamp)->values();

        // Pagination manuelle (les deux sources sont fusionnées en mémoire)
        $page    = max(1, (int) request('page', 1));
        $perPage = 15;
        $total   = $items->count();
        $transactions = new \Illuminate\Pagination\LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('dashboard.abonnement.historique', compact('transactions'));
    }

    /**
     * Construit une entrée d'historique unifiée à partir d'un abonnement
     * (souscription classique ou demande d'option boutique par virement).
     */
    private function transactionDepuisAbonnement(Abonnement $abo, ?PaymentTransaction $tx = null): array
    {
        $isBoutique = ($abo->metadata['type'] ?? null) === 'ajout_option_boutique'
            || $abo->periode === 'option_boutique';

        $typeLabel = $isBoutique
            ? 'Option boutique en ligne'
            : ($abo->montant > 0 ? 'Abonnement' : 'Abonnement offert');

        $titre = $isBoutique ? 'Boutique en ligne' : ($abo->plan?->nom ?? 'Abonnement');

        [$statut, $statutLabel] = $this->normaliserStatut($abo->statut);

        $factureUrl = $this->factureUrlAbonnement($abo);

        return [
            'id'                => $abo->id,
            'titre'             => $titre,
            'type_label'        => $typeLabel,
            'montant'           => (int) $abo->montant,
            'statut'            => $statut,
            'statut_label'      => $statutLabel,
            'periode_label'     => $this->periodeLabel($abo->periode),
            'date'              => $abo->created_at,
            'debut_le'          => $abo->debut_le,
            'expire_le'         => $abo->expire_le,
            'jours_restants'    => $abo->isActif() ? $abo->joursRestants() : null,
            'reference'         => $tx?->reference ?? $abo->reference_transfert,
            'gateway_reference' => $tx?->gateway_reference,
            'methode'           => $tx ? $this->methodeLabel($tx->payment_method_code) : 'Virement bancaire',
            'paid_at'           => $tx?->paid_at,
            'notes_admin'       => $abo->notes_admin,
            'is_boutique'       => $isBoutique,
            'institut_nom'      => $this->institutNomPour($abo),
            'facture_url'       => $factureUrl,
        ];
    }

    /**
     * Construit une entrée d'historique unifiée à partir d'un paiement
     * boutique en ligne individuel (GeniusPay).
     */
    private function transactionDepuisPaiement(PaymentTransaction $tx): array
    {
        [$statut, $statutLabel] = $this->normaliserStatut($tx->status);

        return [
            'id'                => $tx->id,
            'titre'             => 'Boutique en ligne',
            'type_label'        => 'Option boutique en ligne',
            'montant'           => (int) $tx->amount,
            'statut'            => $statut,
            'statut_label'      => $statutLabel,
            'periode_label'     => $tx->type === 'boutique_renouvellement' ? 'Renouvellement' : 'Option boutique',
            'date'              => $tx->created_at,
            'debut_le'          => null,
            'expire_le'         => $tx->abonnement?->expire_le,
            'jours_restants'    => null,
            'reference'         => $tx->reference,
            'gateway_reference' => $tx->gateway_reference,
            'methode'           => $this->methodeLabel($tx->payment_method_code),
            'paid_at'           => $tx->paid_at,
            'notes_admin'       => null,
            'is_boutique'       => true,
            'institut_nom'      => $this->institutNomPourPaiement($tx),
            'facture_url'       => route('abonnement.facture-transaction', $tx),
        ];
    }

    private function normaliserStatut(string $raw): array
    {
        return match ($raw) {
            'actif'      => ['actif', 'Actif'],
            'completed'  => ['paye', 'Payé'],
            'en_attente' => ['en_attente', 'En attente'],
            'pending'    => ['en_attente', 'En attente'],
            'processing' => ['en_attente', 'En traitement'],
            'rejete'     => ['rejete', 'Rejeté'],
            'expire'     => ['expire', 'Expiré'],
            'failed'     => ['echoue', 'Échoué'],
            'cancelled'  => ['annule', 'Annulé'],
            'expired'    => ['annule', 'Expiré'],
            'refunded'   => ['rembourse', 'Remboursé'],
            default      => [strtolower($raw), ucfirst($raw)],
        };
    }

    private function periodeLabel(string $periode): string
    {
        return match ($periode) {
            'mensuel'        => 'Mensuel',
            'trimestre'      => 'Trimestriel',
            'semestre'       => 'Semestriel',
            'annuel'         => '1 an',
            'triennal'       => '3 ans',
            'option_boutique'=> 'Option boutique',
            default          => ucfirst($periode),
        };
    }

    private function methodeLabel(string $code): string
    {
        return $code === 'geniuspay' ? 'GeniusPay' : 'Virement bancaire';
    }

    private function factureUrlAbonnement(Abonnement $abo): string
    {
        return route('abonnement.facture-abonnement', $abo);
    }

    /**
     * Nom de l'établissement concerné par une demande d'option boutique.
     */
    private function institutNomPour(Abonnement $abo): ?string
    {
        if (($abo->metadata['type'] ?? null) === 'ajout_option_boutique') {
            $institut = Institut::find($abo->metadata['institut_id'] ?? null);
            if ($institut) {
                return $institut->nom;
            }
        }
        return null;
    }

    private function institutNomPourPaiement(PaymentTransaction $tx): ?string
    {
        $institut = Institut::find($tx->institut_id ?? $tx->metadata['institut_id'] ?? null);
        return $institut?->nom;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Factures (PDF)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Facture PDF d'un abonnement (souscription ou option boutique).
     */
    public function factureAbonnement(Abonnement $abonnement)
    {
        $this->autoriserFacture($abonnement->user_id);

        $isBoutique = ($abonnement->metadata['type'] ?? null) === 'ajout_option_boutique'
            || $abonnement->periode === 'option_boutique';

        // Paiement GeniusPay rattaché éventuellement à cet abonnement
        $tx = PaymentTransaction::where('abonnement_id', $abonnement->id)
            ->whereIn('type', ['abonnement', 'renouvellement', 'upgrade'])
            ->latest()
            ->first();

        [$statut, $statutLabel] = $this->normaliserStatut($abonnement->statut);

        $details = [
            ['label' => 'Référence', 'value' => $tx?->reference ?? $abonnement->reference_transfert ?: '—'],
            ['label' => 'Méthode de paiement', 'value' => $tx ? $this->methodeLabel($tx->payment_method_code) : 'Virement bancaire'],
            ['label' => 'Statut', 'value' => $statutLabel],
        ];

        if ($tx?->gateway_reference) {
            $details[] = ['label' => 'Référence passerelle', 'value' => $tx->gateway_reference];
        }
        if ($abonnement->debut_le && $abonnement->expire_le) {
            $details[] = ['label' => 'Période', 'value' => 'Du ' . $abonnement->debut_le->format('d/m/Y') . ' au ' . $abonnement->expire_le->format('d/m/Y')];
        }
        if ($tx?->paid_at) {
            $details[] = ['label' => 'Payé le', 'value' => $tx->paid_at->format('d/m/Y H:i')];
        }
        if ($abonnement->notes_admin) {
            $details[] = ['label' => 'Notes', 'value' => $abonnement->notes_admin];
        }

        $institutNom = $this->institutNomPour($abonnement);

        $invoice = $this->factureDonnees(
            id: $abonnement->id,
            createdAt: $abonnement->created_at,
            typeLabel: $isBoutique ? 'Option boutique en ligne' : 'Abonnement',
            designation: $isBoutique
                ? 'Option boutique en ligne' . ($institutNom ? ' — ' . $institutNom : '')
                : ($abonnement->plan?->nom ?? 'Abonnement') . ' — ' . $this->periodeLabel($abonnement->periode),
            montant: (int) $abonnement->montant,
            methode: $tx ? $this->methodeLabel($tx->payment_method_code) : 'Virement bancaire',
            statutLabel: $statutLabel,
            reference: $tx?->reference ?? $abonnement->reference_transfert,
            details: $details,
        );

        $pdf = Pdf::loadView('pdf.facture-abonnement', compact('invoice'))->setPaper('a4', 'portrait');
        return $pdf->download('facture-' . $invoice['numero'] . '.pdf');
    }

    /**
     * Facture PDF d'un paiement boutique en ligne individuel (GeniusPay).
     */
    public function factureTransaction(PaymentTransaction $paymentTransaction)
    {
        $this->autoriserFacture($paymentTransaction->user_id);

        $paymentTransaction->loadMissing('abonnement.plan', 'user');

        [$statut, $statutLabel] = $this->normaliserStatut($paymentTransaction->status);

        $institutNom = $this->institutNomPourPaiement($paymentTransaction);

        $details = [
            ['label' => 'Référence', 'value' => $paymentTransaction->reference],
            ['label' => 'Méthode de paiement', 'value' => $this->methodeLabel($paymentTransaction->payment_method_code)],
            ['label' => 'Statut', 'value' => $statutLabel],
        ];

        if ($paymentTransaction->gateway_reference) {
            $details[] = ['label' => 'Référence passerelle', 'value' => $paymentTransaction->gateway_reference];
        }
        if ($paymentTransaction->paid_at) {
            $details[] = ['label' => 'Payé le', 'value' => $paymentTransaction->paid_at->format('d/m/Y H:i')];
        }
        if ($paymentTransaction->abonnement?->expire_le) {
            $details[] = ['label' => 'Valable jusqu\'au', 'value' => $paymentTransaction->abonnement->expire_le->format('d/m/Y')];
        }

        $invoice = $this->factureDonnees(
            id: $paymentTransaction->id,
            createdAt: $paymentTransaction->created_at,
            typeLabel: 'Option boutique en ligne',
            designation: 'Option boutique en ligne' . ($institutNom ? ' — ' . $institutNom : ''),
            montant: (int) $paymentTransaction->amount,
            methode: $this->methodeLabel($paymentTransaction->payment_method_code),
            statutLabel: $statutLabel,
            reference: $paymentTransaction->reference,
            details: $details,
        );

        $pdf = Pdf::loadView('pdf.facture-abonnement', compact('invoice'))->setPaper('a4', 'portrait');
        return $pdf->download('facture-' . $invoice['numero'] . '.pdf');
    }

    /**
     * Vérifie que l'utilisateur connecté est bien le destinataire de la facture.
     */
    private function autoriserFacture(?string $userId): void
    {
        abort_unless($userId && $userId === Auth::id(), 403);
    }

    /**
     * Construit le tableau de données normalisé transmis à la vue PDF.
     */
    private function factureDonnees(
        string $id,
        \Illuminate\Support\Carbon $createdAt,
        string $typeLabel,
        string $designation,
        int $montant,
        string $methode,
        string $statutLabel,
        ?string $reference,
        array $details,
    ): array {
        $user = Auth::user();

        return [
            'numero'        => 'FAC-' . $createdAt->format('Ymd') . '-' . strtoupper(substr($id, 0, 6)),
            'date_emission' => now(),
            'type_label'    => $typeLabel,
            'designation'   => $designation,
            'montant'       => $montant,
            'devise'        => 'FCFA',
            'methode'       => $methode,
            'statut_label'  => $statutLabel,
            'reference'     => $reference,
            'details'       => $details,
            'client'        => [
                'nom'        => $user->nom_complet ?: $user->name,
                'email'      => $user->email,
                'telephone'  => $user->telephone,
                'institut'   => Institut::find($user->currentInstitutId())?->nom ?? $user->institut?->nom,
            ],
        ];
    }

    public function plans()
    {
        $plans = PlanAbonnement::where('actif', true)
            ->whereIn('slug', ['premium', 'premium-plus', 'ultra', 'entreprise'])
            ->orderBy('ordre')
            ->get();

        $user = Auth::user();
        // Pour un non-propriétaire, utiliser l'abonnement du propriétaire
        $institut = \App\Models\Institut::find($user->currentInstitutId());
        if ($institut && $institut->proprietaire_id !== $user->id) {
            $owner = \App\Models\User::find($institut->proprietaire_id);
            $abonnementActif = $owner?->abonnementActif;
        } else {
            $abonnementActif = $user->abonnementActif;
        }

        $demandeEnAttente = Abonnement::where('user_id', $user->id)
            ->where('statut', 'en_attente')
            ->with('plan')
            ->first();

        $abonnementSursis = $abonnementActif ? null : $user->abonnementEnSursis();

        return view('dashboard.abonnement.plans', compact('plans', 'abonnementActif', 'demandeEnAttente', 'abonnementSursis'));
    }

    /**
     * Affiche la page de souscription complète pour un plan donné.
     */
    public function showSouscrire(Request $request, PlanAbonnement $plan)
    {
        if (!$plan->actif || $plan->slug === 'essai') {
            return redirect()->route('abonnement.plans')
                ->with('error', "Ce plan n'est pas disponible.");
        }

        $periode = $request->query('periode', 'mensuel');
        if (!in_array($periode, ['mensuel', 'trimestre', 'semestre', 'annuel', 'triennal'])) {
            $periode = 'mensuel';
        }

        $user = Auth::user();
        $abonnementActif = $user->abonnementActif;

        $demandeEnAttente = Abonnement::where('user_id', $user->id)
            ->where('statut', 'en_attente')
            ->with('plan')
            ->first();

        // ── Facturation PAR établissement ─────────────────────────────────────
        // On transmet TOUS les établissements du compte avec leur état d'option
        // boutique : pré-cochés si l'option est active, décochés sinon.
        $estRenouvellement = (bool) $abonnementActif;
        $etablissements = $user->mesInstituts()
            ->get()
            ->map(fn ($i) => [
                'id'         => $i->id,
                'nom'        => $i->nom,
                'slug'       => $i->slug,
                'ville'      => $i->ville,
                'optionActive' => $i->hasBoutiqueOption(),
            ])
            ->values();

        // Prix pour la période sélectionnée
        $prixPlan       = $plan->prixEffectif($periode);
        $paymentMethods = PaymentMethod::active()->get();

        return view('dashboard.abonnement.souscrire', compact(
            'plan', 'periode', 'prixPlan',
            'abonnementActif', 'demandeEnAttente', 'user', 'paymentMethods',
            'estRenouvellement', 'etablissements'
        ));
    }

    public function souscrire(Request $request, PlanAbonnement $plan)
    {
        if (!$plan->actif || $plan->slug === 'essai') {
            return back()->with('error', "Ce plan n'est pas disponible.");
        }

        $request->validate([
            'periode'             => ['required', 'in:mensuel,trimestre,semestre,annuel,triennal'],
            'methode_paiement'    => ['nullable', 'string', 'in:geniuspay,bank_transfer'],
            'reference_transfert' => ['nullable', 'string', 'max:100'],
            'preuve_paiement'     => ['nullable', 'file', 'mimes:jpeg,png,jpg,webp,pdf', 'max:10240'],
        ]);

        $methodCode = $request->input('methode_paiement', 'bank_transfer');
        $method     = PaymentMethod::where('code', $methodCode)->where('is_active', true)->first();

        // Forcer bank_transfer si GeniusPay non disponible
        if ($methodCode === 'geniuspay' && !$method) {
            $methodCode = 'bank_transfer';
        }

        // ── Flux GeniusPay ──────────────────────────────────────────────────
        if ($methodCode === 'geniuspay' && $method) {
            return $this->souscrireViaGeniusPay($request, $plan, $method);
        }

        // ── Flux transfert bancaire (existant inchangé) ─────────────────────
        if (!$request->reference_transfert && !$request->hasFile('preuve_paiement')) {
            return back()->with('error', 'Veuillez fournir au moins la référence du transfert ou le reçu de paiement.');
        }

        $user = Auth::user();

        $demandeExistante = Abonnement::where('user_id', $user->id)
            ->where('statut', 'en_attente')
            ->exists();

        if ($demandeExistante) {
            return back()->with('error', 'Vous avez déjà une demande en attente de validation.');
        }

        $preuvePath = $request->hasFile('preuve_paiement')
            ? $request->file('preuve_paiement')->store('preuves-paiement', 'public')
            : null;
        $montant = $plan->prixPourPeriode($request->periode);

        // Option boutique en ligne (add-on payant, facturation PAR établissement)
        // L'utilisateur a coché les établissements souhaités (boutiques[]).
        $nbMois = match ($request->periode) {
            'mensuel'  => 1,
            'semestre' => 6,
            'annuel'   => 12,
            'triennal' => 36,
            default    => 1,
        };

        $institutIds = collect($request->input('boutiques', []))
            ->filter()
            ->unique()
            ->values()
            ->all();

        // Ne garder que les établissements du compte (sécurité)
        $etablissementsValides = $user->mesInstituts()
            ->whereIn('id', $institutIds)
            ->pluck('id')
            ->all();

        $nbBoutiques = count($etablissementsValides);
        $prixBoutique = 3900 * $nbMois * $nbBoutiques;

        Abonnement::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'montant' => $montant + $prixBoutique,
            'periode' => $request->periode,
            'statut' => 'en_attente',
            'reference_transfert' => $request->reference_transfert,
            'preuve_paiement' => $preuvePath,
            'metadata' => [
                'boutique' => $nbBoutiques > 0,
                'boutique_prix' => $nbBoutiques > 0 ? 3900 : 0,
                'nb_boutiques' => $nbBoutiques,
                'boutiques_ids' => $etablissementsValides,
            ],
        ]);

        // Notifier tous les super-admins par email
        $superAdmins = User::where('role', 'super_admin')->get();
        $abonnement  = Abonnement::where('user_id', $user->id)
            ->where('statut', 'en_attente')
            ->with(['user', 'plan'])
            ->latest()
            ->first();

        foreach ($superAdmins as $admin) {
            Mail::to($admin->email)->send(new NouvelleDemandeAbonnement($abonnement));
        }
        \App\Services\NotificationService::notifyAdmins(
            'nouvelle_demande',
            '💳 Nouvelle demande — ' . ($abonnement?->plan?->nom ?? 'Plan'),
            ($user->prenom ?? $user->name) . ' attend la validation de son abonnement.',
            '/admin/abonnements?statut=en_attente'
        );
        try {
            app(\App\Services\PushNotificationService::class)->sendToAdmins(
                '💳 Nouvelle demande d\'abonnement',
                ($user->prenom ?? '') . ' (' . ($abonnement?->plan?->nom ?? 'Plan') . ') attend votre validation.',
                '/admin/abonnements?statut=en_attente'
            );
        } catch (\Throwable $e) { \Log::warning('[Push] ' . $e->getMessage()); }

        return redirect()->route('abonnement.plans')
            ->with('success', "Votre demande d'abonnement a été envoyée ! Elle sera validée sous 24h.");
    }

    /**
     * Ajouter l'option boutique à un abonnement existant
     */
    public function ajouterOptionBoutique(Request $request)
    {
        $user = Auth::user();
        $abo = $user->abonnementActif;

        // L'établissement concerné = établissement courant (session multi-instituts)
        $institut = Institut::find(session('current_institut_id', $user->institut_id));
        if (!$institut) {
            return back()->with('error', 'Établissement introuvable.');
        }

        // Vérifications de base
        if (!$abo || $abo->plan->slug === 'essai') {
            return back()->with('error', 'Action non disponible.');
        }

        if ($institut->hasBoutiqueOption()) {
            return back()->with('info', 'L\'option boutique est déjà activée sur cet établissement.');
        }

        // Vérifier qu'il n'y a pas déjà une demande en attente POUR CET ÉTABLISSEMENT
        $demandeExistante = Abonnement::where('user_id', $user->id)
            ->where('statut', 'en_attente')
            ->whereJsonContains('metadata->type', 'ajout_option_boutique')
            ->whereJsonContains('metadata->institut_id', $institut->id)
            ->exists();

        if ($demandeExistante) {
            return back()->with('error', 'Vous avez déjà une demande d\'ajout d\'option boutique en attente pour cet établissement.');
        }

        // Validation des données de paiement
        $request->validate([
            'methode_paiement'    => 'nullable|string|in:geniuspay,bank_transfer',
            'reference_transfert' => 'nullable|string|max:255',
            'preuve_paiement'     => 'nullable|file|mimes:jpeg,jpg,png,webp,pdf|max:10240',
        ]);

        $methodCode = $request->input('methode_paiement', 'bank_transfer');
        $method     = PaymentMethod::where('code', $methodCode)->where('is_active', true)->first();

        // ── Flux GeniusPay pour boutique ─────────────────────────────────────
        if ($methodCode === 'geniuspay' && $method) {
            return $this->activerBoutiqueViaGeniusPay($abo, $method, $institut);
        }

        // Au moins une preuve de paiement
        if (!$request->reference_transfert && !$request->hasFile('preuve_paiement')) {
            return back()->with('error', 'Veuillez fournir une référence de transfert ou un reçu de paiement.');
        }

        // Upload du reçu si présent
        $preuvePath = null;
        if ($request->hasFile('preuve_paiement')) {
            $preuvePath = $request->file('preuve_paiement')->store('abonnements/preuves', 'public');
        }

        // Calculer le prorata pour le reste de la période
        $joursRestants = max(1, $abo->joursRestants());
        $prixJournalier = 3900 / 30;
        $montantProrata = (int) round($prixJournalier * $joursRestants);

        // Créer une demande d'ajout d'option (en_attente)
        $nouvelAbo = Abonnement::create([
            'user_id'    => $user->id,
            'plan_id'    => $abo->plan_id,
            'montant'    => $montantProrata,
            'periode'    => 'option_boutique',
            'statut'     => 'en_attente',
            'reference_transfert' => $request->reference_transfert,
            'preuve_paiement' => $preuvePath,
            'metadata'   => [
                'type' => 'ajout_option_boutique',
                'abonnement_source_id' => $abo->id,
                'institut_id' => $institut->id,
                'boutique' => true,
                'boutique_prix' => 3900,
                'jours_restants' => $joursRestants,
            ],
        ]);

        // Notifier les super-admins
        $superAdmins = User::where('role', 'super_admin')->get();
        foreach ($superAdmins as $admin) {
            Mail::to($admin->email)->send(new \App\Mail\NouvelleDemandeAbonnement($nouvelAbo));
        }

        NotificationService::notifyAdmins(
            'nouvelle_demande',
            '🛍️ Ajout option boutique',
            ($user->prenom ?? $user->name) . ' demande à ajouter l\'option boutique en ligne (prorata ' . number_format($montantProrata, 0, ',', ' ') . ' F).',
            '/admin/abonnements?statut=en_attente'
        );

        try {
            app(\App\Services\PushNotificationService::class)->sendToAdmins(
                '🛍️ Ajout option boutique',
                ($user->prenom ?? '') . ' demande l\'option boutique (' . number_format($montantProrata, 0, ',', ' ') . ' F prorata).',
                '/admin/abonnements?statut=en_attente'
            );
        } catch (\Throwable $e) { \Log::warning('[Push] ' . $e->getMessage()); }

        return redirect()->route('dashboard.boutique.config.index')
            ->with('success', 'Demande d\'ajout de l\'option boutique envoyée ! Montant prorata : ' . number_format($montantProrata, 0, ',', ' ') . ' FCFA. Vous serez notifié dès la validation.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Méthodes privées GeniusPay
    // ─────────────────────────────────────────────────────────────────────────

    private function souscrireViaGeniusPay(Request $request, PlanAbonnement $plan, PaymentMethod $method)
    {
        $user    = Auth::user();
        $periode = $request->input('periode', 'mensuel');
        $montant = $plan->prixPourPeriode($periode);
        $nbMois  = match ($periode) { 'trimestre' => 3, 'semestre' => 6, 'annuel' => 12, 'triennal' => 36, default => 1 };

        // ── Option boutique (facturation PAR établissement) ──────────────────
        // L'utilisateur a coché les établissements souhaités (boutiques[]).
        // On ne garde que les établissements du compte (sécurité).
        $institutIds = collect($request->input('boutiques', []))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $etablissementsValides = $user->mesInstituts()
            ->whereIn('id', $institutIds)
            ->pluck('id')
            ->all();

        $nbBoutiques = count($etablissementsValides);

        if ($nbBoutiques > 0) {
            $montant += 3900 * $nbMois * $nbBoutiques;
        }

        // Créer l'abonnement en_attente (sera activé auto par le webhook)
        $abonnement = Abonnement::create([
            'user_id'  => $user->id,
            'plan_id'  => $plan->id,
            'montant'  => $montant,
            'periode'  => $periode,
            'statut'   => 'en_attente',
            'metadata' => [
                'boutique'       => $nbBoutiques > 0,
                'boutique_prix'  => $nbBoutiques > 0 ? 3900 : 0,
                'nb_boutiques'   => $nbBoutiques,
                'boutiques_ids'  => $etablissementsValides,
                'payment_method' => 'geniuspay',
            ],
        ]);

        // Créer la transaction de paiement
        $transaction = PaymentTransaction::create([
            'id'                  => (string) \Illuminate\Support\Str::uuid(),
            'reference'           => PaymentTransaction::generateReference(),
            'user_id'             => $user->id,
            'institut_id'         => $user->institut_id,
            'abonnement_id'       => $abonnement->id,
            'type'                => $user->abonnementActif ? 'renouvellement' : 'abonnement',
            'amount'              => $montant,
            'net_amount'          => $montant,
            'currency'            => 'XOF',
            'payment_method_id'   => $method->id,
            'payment_method_code' => 'geniuspay',
            'status'              => 'pending',
            'metadata'            => [
                'plan_nom'     => $plan->nom,
                'periode'      => $periode,
                'nb_boutiques' => $nbBoutiques,
                'boutiques_ids'=> $etablissementsValides,
            ],
        ]);

        try {
            $result = app(PaymentGatewayManager::class)->initiate($transaction, $method);
            return redirect($result['checkout_url']);
        } catch (\Throwable $e) {
            $transaction->update(['status' => 'failed']);
            $abonnement->delete();
            Log::error('[GeniusPay souscrire] ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de la redirection vers GeniusPay. Veuillez réessayer ou utiliser le transfert bancaire.');
        }
    }

    private function activerBoutiqueViaGeniusPay(Abonnement $abo, PaymentMethod $method, ?Institut $institut = null)
    {
        $user           = Auth::user();
        $institut       = $institut ?? Institut::find(session('current_institut_id', $user->institut_id));
        $joursRestants  = max(1, $abo->joursRestants());
        $montantProrata = (int) round(3900 / 30 * $joursRestants);

        $transaction = PaymentTransaction::create([
            'id'                  => (string) \Illuminate\Support\Str::uuid(),
            'reference'           => PaymentTransaction::generateReference(),
            'user_id'             => $user->id,
            'institut_id'         => $institut?->id ?? $user->institut_id,
            'abonnement_id'       => $abo->id,
            'type'                => 'boutique_activation',
            'amount'              => $montantProrata,
            'net_amount'          => $montantProrata,
            'currency'            => 'XOF',
            'payment_method_id'   => $method->id,
            'payment_method_code' => 'geniuspay',
            'status'              => 'pending',
            'metadata'            => [
                'abonnement_source_id' => $abo->id,
                'institut_id'          => $institut?->id,
                'jours_restants'       => $joursRestants,
            ],
        ]);

        try {
            $result = app(PaymentGatewayManager::class)->initiate($transaction, $method);
            return redirect($result['checkout_url']);
        } catch (\Throwable $e) {
            $transaction->update(['status' => 'failed']);
            Log::error('[GeniusPay boutique] ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de la redirection vers GeniusPay. Veuillez réessayer ou utiliser le transfert bancaire.');
        }
    }
}

