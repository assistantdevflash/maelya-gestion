<?php

namespace App\Http\Controllers;

use App\Mail\NouveauRdvVitrineClient;
use App\Mail\NouveauRdvVitrineEtablissement;
use App\Models\Institut;
use App\Models\Prestation;
use App\Models\RendezVous;
use App\Services\NotificationService;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class VitrineController extends Controller
{
    public function show(string $slug)
    {
        $institut = Institut::where('slug', $slug)
            ->where('vitrine_active', true)
            ->where('actif', true)
            ->firstOrFail();

        $prestations = $institut->prestations()
            ->where('actif', true)
            ->with('categorie')
            ->orderBy('nom')
            ->get()
            ->groupBy(fn($p) => $p->categorie?->nom ?? 'Autres');

        $produits = $institut->produits()
            ->where('actif', true)
            ->with('categorie')
            ->orderBy('nom')
            ->get()
            ->groupBy(fn($p) => $p->categorie?->nom ?? 'Autres');

        $prestationsFlat = $institut->prestations()->with('categorie')->where('actif', true)->orderBy('nom')->get(['id', 'nom', 'prix', 'duree', 'categorie_id']);

        $avis = \App\Models\AvisClient::withoutGlobalScopes()
            ->where('institut_id', $institut->id)
            ->where('statut', 'approuve')
            ->whereNotNull('repondu_le')
            ->orderByDesc('repondu_le')
            ->limit(10)
            ->get();

        $noteMoyenne = $avis->avg('note');
        $nbAvis      = $avis->count();

        return view('vitrine.show', compact('institut', 'prestations', 'produits', 'prestationsFlat', 'avis', 'noteMoyenne', 'nbAvis'));
    }

    public function reserver(Request $request, string $slug)
    {
        $institut = Institut::where('slug', $slug)
            ->where('vitrine_active', true)
            ->where('actif', true)
            ->firstOrFail();

        $data = $request->validate([
            'client_nom'       => ['required', 'string', 'max:150'],
            'client_telephone' => ['required', 'string', 'max:30'],
            'client_email'     => ['nullable', 'email', 'max:255'],
            'prestations'      => ['required', 'array', 'min:1'],
            'prestations.*'    => ['required', 'uuid'],
            'debut_le'         => ['required', 'date', 'after:now'],
            'notes'            => ['nullable', 'string', 'max:500'],
        ]);

        // Vérifier que toutes les prestations appartiennent bien à cet institut
        $prestations = Prestation::whereIn('id', $data['prestations'])
            ->where('institut_id', $institut->id)
            ->where('actif', true)
            ->get();

        if ($prestations->isEmpty()) {
            return back()->withErrors(['prestations' => 'Veuillez sélectionner au moins une prestation valide.'])->withInput();
        }

        $dureeTotal = $prestations->sum('duree') ?: 30;

        $rdv = RendezVous::create([
            'institut_id'      => $institut->id,
            'client_nom'       => $data['client_nom'],
            'client_telephone' => $data['client_telephone'],
            'client_email'     => $data['client_email'] ?? null,
            'debut_le'         => $data['debut_le'],
            'duree_minutes'    => $dureeTotal,
            'statut'           => 'en_attente',
            'notes'            => $data['notes'] ?? null,
            'source'           => 'vitrine',
        ]);

        $rdv->prestations()->attach($prestations->pluck('id'));

        // ── Email à l'admin/propriétaire du salon (sync comme les autres mails) ──
        $adminEmail = $institut->email ?? $institut->proprietaire?->email;
        if ($adminEmail) {
            Mail::to($adminEmail)->send(new NouveauRdvVitrineEtablissement($rdv));
        }

        // ── Email de confirmation au client (si email fourni) ──────────────
        if (!empty($data['client_email'])) {
            Mail::to($data['client_email'])->send(new NouveauRdvVitrineClient($rdv));
        }

        // ── Notification in-app + push pour le propriétaire du salon ──────
        $proprietaire = $institut->proprietaire;
        if ($proprietaire) {
            $rdvUrl = route('dashboard.rdv.show', $rdv);
            $prestationsLabel = $prestations->pluck('nom')->join(', ');
            $titre   = '📅 Nouvelle demande de RDV';
            $message = $data['client_nom'] . ' — ' . $prestationsLabel;

            NotificationService::notifyUser($proprietaire, 'nouveau_rdv_vitrine', $titre, $message, $rdvUrl);

            try {
                app(PushNotificationService::class)->sendToUser($proprietaire, $titre, $message, $rdvUrl);
            } catch (\Throwable $e) {
                \Log::warning('[Push vitrine] ' . $e->getMessage());
            }
        }

        return back()->with('success', 'Votre demande de rendez-vous a bien été enregistrée. Nous vous recontacterons pour confirmation.');
    }
}
