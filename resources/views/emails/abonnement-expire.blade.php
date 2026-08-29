<x-emails.app
    titre="Votre abonnement a expiré"
    sousTitre="Bonjour {{ $abonnement->user->prenom ?? $abonnement->user->name }}, votre accès est désormais en lecture seule."
>
    <div class="alert-danger">
        ⚠️ &nbsp;Votre abonnement <strong>{{ $abonnement->plan->nom ?? 'Premium' }}</strong> a expiré le <strong>{{ $abonnement->expire_le?->format('d/m/Y') }}</strong>.
        Votre espace est maintenant en <strong>mode lecture seule</strong> : vous pouvez consulter vos données mais plus les modifier.
    </div>

    <p class="section-title">Abonnement expiré</p>
    <div class="card">
        <div class="row">
            <span class="label">Plan</span>
            <span class="value">{{ $abonnement->plan->nom ?? '—' }}</span>
        </div>
        <div class="row">
            <span class="label">Expiré le</span>
            <span class="value" style="color:#dc2626;">{{ $abonnement->expire_le?->format('d/m/Y') ?? '—' }}</span>
        </div>
    </div>

    <p class="section-title">Ce qui est restreint</p>
    <div class="card">
        @foreach(['Enregistrement de nouvelles ventes', 'Ajout et modification de clients', 'Gestion des stocks et prestations', 'Création de codes de réduction'] as $item)
        <div class="row">
            <span style="display:inline-flex;width:22px;height:22px;background:#fee2e2;border-radius:50%;align-items:center;justify-content:center;font-size:11px;margin-right:10px;flex-shrink:0;">✕</span>
            <span class="value" style="font-weight:500;">{{ $item }}</span>
        </div>
        @endforeach
    </div>

    <div class="cta">
        <a href="{{ route('abonnement.plans') }}" class="btn">
            Renouveler mon abonnement →
        </a>
    </div>
    <p style="text-align:center;font-size:12px;color:#9ca3af;margin-top:12px;">
        Vos données sont conservées intégralement. La réactivation est instantanée après validation.
    </p>
</x-emails::app>
