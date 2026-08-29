@php
    $rappelTitre = $joursRestants === 1
        ? 'Votre abonnement expire demain'
        : "Votre abonnement expire dans {$joursRestants} jours";
@endphp
<x-emails.app
    titre="{{ $rappelTitre }}"
    sousTitre="Bonjour {{ $abonnement->user->prenom ?? $abonnement->user->name }}, pensez à renouveler pour ne pas interrompre votre activité."
>
    <div class="alert-warning">
        ⏳ &nbsp;Votre abonnement <strong>{{ $abonnement->plan->nom ?? 'Premium' }}</strong> expire le <strong>{{ $abonnement->expire_le?->format('d/m/Y') }}</strong>.
        @if($joursRestants === 1)
            C'est <strong>demain</strong> — renouvelez maintenant pour éviter toute interruption.
        @else
            Il vous reste <strong>{{ $joursRestants }} jours</strong> pour renouveler.
        @endif
    </div>

    <p class="section-title">Abonnement en cours</p>
    <div class="card">
        <div class="row">
            <span class="label">Plan</span>
            <span class="value">{{ $abonnement->plan->nom ?? '—' }}</span>
        </div>
        <div class="row">
            <span class="label">Expire le</span>
            <span class="value" style="color:#d97706;">{{ $abonnement->expire_le?->format('d/m/Y') ?? '—' }}</span>
        </div>
    </div>

    <p class="section-title">Si vous ne renouvelez pas</p>
    <div class="card">
        @foreach(['Votre espace passera en mode lecture seule', 'Plus de nouvelles ventes ni de modifications', 'Vos données restent conservées intégralement', 'La réactivation est instantanée après paiement'] as $item)
        <div class="row">
            <span style="display:inline-flex;width:22px;height:22px;background:#fef3c7;border-radius:50%;align-items:center;justify-content:center;font-size:11px;margin-right:10px;flex-shrink:0;">!</span>
            <span class="value" style="font-weight:500;">{{ $item }}</span>
        </div>
        @endforeach
    </div>

    <div class="cta">
        <a href="{{ route('abonnement.plans') }}" class="btn">
            Renouveler mon abonnement →
        </a>
    </div>
</x-emails::app>
