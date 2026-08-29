<x-emails.app
    titre="Demande non validée"
    sousTitre="Bonjour {{ $abonnement->user->prenom ?? $abonnement->user->name }}"
>
    <div class="alert-danger">
        ❌ &nbsp;Nous avons examiné votre demande d'abonnement <strong>{{ $abonnement->plan?->nom ?? '' }}</strong> et nous ne sommes malheureusement pas en mesure de la valider pour le moment.
    </div>

    @if($abonnement->notes_admin)
    <p class="section-title">Motif communiqué</p>
    <div class="alert-danger">
        {{ $abonnement->notes_admin }}
    </div>
    @endif

    <p class="section-title">Votre demande</p>
    <div class="card">
        <div class="row">
            <span class="label">Plan</span>
            <span class="value">{{ $abonnement->plan?->nom ?? '—' }}</span>
        </div>
        <div class="row">
            <span class="label">Montant</span>
            <span class="value">{{ number_format($abonnement->montant, 0, ',', ' ') }} FCFA</span>
        </div>
        @if($abonnement->hasBoutique())
        <div class="row">
            <span class="label">Boutique en ligne</span>
            <span class="value" style="color:#7c3aed;">🛍️ Incluse ({{ number_format($abonnement->metadata['boutique_prix'] ?? 3900, 0, ',', ' ') }} FCFA)</span>
        </div>
        @endif
        <div class="row">
            <span class="label">Date de demande</span>
            <span class="value">{{ $abonnement->created_at->format('d/m/Y') }}</span>
        </div>
    </div>

    <p style="font-size:14px;color:#374151;line-height:1.6;margin:0 0 8px;">
        Si vous pensez qu'il s'agit d'une erreur ou souhaitez plus d'informations, n'hésitez pas à nous contacter ou à soumettre une nouvelle demande depuis votre espace.
    </p>

    <div class="cta">
        <a href="{{ config('app.url') }}/abonnement/plans" class="btn">
            Voir les plans disponibles
        </a>
    </div>
</x-emails::app>
