@php
    $cp = $institut->couleur_primaire ?? '#d97706';
    $cs = $institut->couleur_secondaire ?? '#ec4899';
    $ca = $institut->couleur_accent ?? '#f59e0b';
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 2cm 1.8cm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #1f2937; line-height: 1.5; }

        .header { text-align: center; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 2px solid #d1d5db; }
        .header .institut { font-size: 14pt; font-weight: bold; color: {{ $cp }}; margin: 0; }
        .header .sous   { font-size: 8pt; color: #6b7280; margin: 2px 0 0 0; }

        .title { font-size: 11pt; font-weight: bold; color: #374151; margin: 16px 0 8px 0; padding-bottom: 4px; border-bottom: 1px solid #e5e7eb; text-transform: uppercase; letter-spacing: .08em; }

        .identity { width: 100%; margin-bottom: 10px; }
        .identity td { vertical-align: top; padding: 2px 8px 2px 0; }
        .identity .label { color: #9ca3af; font-size: 7pt; text-transform: uppercase; letter-spacing: .06em; white-space: nowrap; width: 1%; }
        .identity .value { font-size: 10pt; color: #1f2937; }
        .identity .name  { font-size: 14pt; font-weight: bold; color: #111827; padding-bottom: 8px; }

        .kpi { width: 100%; margin: 12px 0 18px 0; border-collapse: collapse; }
        .kpi td { width: 25%; text-align: center; padding: 8px 4px; border: 1px solid #e5e7eb; }
        .kpi .number { font-size: 14pt; font-weight: bold; color: #111827; }
        .kpi .number.amber { color: {{ $ca }}; }
        .kpi .legend { font-size: 7pt; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; }

        table.data { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.data th { background: #f3f4f6; padding: 6px 8px; font-size: 7pt; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; text-align: left; border-bottom: 2px solid #d1d5db; }
        table.data td { padding: 5px 8px; font-size: 8pt; border-bottom: 1px solid #f3f4f6; }
        table.data .amount { text-align: right; font-weight: bold; }
        table.data .date { color: #9ca3af; white-space: nowrap; }

        .footer { margin-top: 24px; padding-top: 10px; border-top: 1px solid #e5e7eb; font-size: 7pt; color: #9ca3af; text-align: center; }
        .badge { display: inline-block; padding: 1px 6px; border-radius: 10px; font-size: 7pt; font-weight: bold; }
        .badge-paid { background: #d1fae5; color: #065f46; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="institut">{{ $institut->nom }}</h1>
        @if($institut->telephone || $institut->email)
        <p class="sous">{{ collect([$institut->telephone, $institut->email])->filter()->implode(' · ') }}</p>
        @endif
    </div>

    <div class="title">Fiche client</div>

    <table class="identity">
        <tr><td colspan="2" class="name">{{ $client->nom_affichage }}</td></tr>
        @if($client->telephone)<tr><td class="label">Téléphone</td><td class="value">{{ $client->telephone }}</td></tr>@endif
        @if($client->email)<tr><td class="label">Email</td><td class="value">{{ $client->email }}</td></tr>@endif
        @if($client->adresse)<tr><td class="label">Adresse</td><td class="value">{{ $client->adresse }}</td></tr>@endif
        @if(!$client->isEntreprise() && $client->date_naissance)<tr><td class="label">Né(e) le</td><td class="value">{{ $client->naissance_formatee }}</td></tr>@endif
        @if($client->isEntreprise() && $client->numero_registre_commerce)<tr><td class="label">RC</td><td class="value">{{ $client->numero_registre_commerce }}</td></tr>@endif
        @if($client->piece_identite)<tr><td class="label">Pièce ID</td><td class="value">{{ $client->piece_identite }}</td></tr>@endif
        <tr><td class="label">Code</td><td class="value" style="font-family: monospace; font-size: 8pt;">{{ $client->code_client ?? substr($client->id,0,12) }}</td></tr>
        <tr><td class="label">Client depuis</td><td class="value">{{ $client->created_at->translatedFormat('d F Y') }}</td></tr>
        @if($client->notes)<tr><td class="label">Notes</td><td class="value" style="font-size:8pt;">{{ $client->notes }}</td></tr>@endif
    </table>

    <table class="kpi">
        <tr>
            <td><div class="number">{{ $client->nombre_visites }}</div><div class="legend">Visites</div></td>
            <td><div class="number">{{ number_format($client->total_depense,0,',',' ') }} F</div><div class="legend">Dépensés</div></td>
            <td><div class="number {{ $client->points_fidelite>0?'amber':'' }}">{{ number_format($client->points_fidelite,0,',',' ') }}</div><div class="legend">Points</div></td>
            <td><div class="number" style="font-size:9pt;">{{ $client->derniere_visite?->diffForHumans(['parts'=>1])??'—' }}</div><div class="legend">Dernière visite</div></td>
        </tr>
    </table>

    @if($show['achats'] && $ventes->isNotEmpty())
    <div class="title">Derniers achats</div>
    <table class="data">
        <thead><tr><th>Date</th><th>Article(s)</th><th style="text-align:right;">Total</th><th style="text-align:center;">Mode</th></tr></thead>
        <tbody>
            @foreach($ventes as $v)
            <tr>
                <td class="date">{{ $v->created_at->format('d/m/Y') }}</td>
                <td>{{ $v->items->pluck('nom_snapshot')->take(2)->implode(', ') }}{{ $v->items->count()>2?' +'.($v->items->count()-2):'' }}</td>
                <td class="amount">{{ number_format($v->total,0,',',' ') }} F</td>
                <td style="text-align:center;font-size:7pt;">{{ $v->mode_paiement==='mobile_money'?'Mobile':($v->mode_paiement==='credit'?'Crédit':'Cash') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if($show['rdv'] && $rdvs->isNotEmpty())
    <div class="title">Rendez-vous</div>
    <table class="data">
        <thead><tr><th>Date</th><th>Prestations</th><th style="text-align:center;">Statut</th></tr></thead>
        <tbody>
            @foreach($rdvs as $r)
            <tr>
                <td class="date">{{ $r->debut_le->format('d/m/Y H:i') }}</td>
                <td>{{ $r->label_prestations ?: '—' }}</td>
                <td style="text-align:center;">
                    @php $b=$r->statut_badge; @endphp
                    <span class="badge {{ $r->statut==='termine'?'badge-paid':($r->statut==='annule'?'badge-cancelled':'badge-pending') }}">{{ $b['label'] }}</span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if($show['credits'] && $credits->isNotEmpty())
    <div class="title">Crédits</div>
    <table class="data">
        <thead><tr><th>Date début</th><th>Articles</th><th style="text-align:right;">Restant</th><th style="text-align:center;">Statut</th></tr></thead>
        <tbody>
            @foreach($credits as $c)
            <tr>
                <td class="date">{{ $c->date_debut->format('d/m/Y') }}</td>
                <td>{{ $c->vente->items->pluck('nom_snapshot')->take(2)->implode(', ') ?: '—' }}</td>
                <td class="amount">{{ number_format($c->reste_a_payer,0,',',' ') }} F</td>
                <td style="text-align:center;"><span class="badge {{ $c->statut==='solde'?'badge-paid':($c->statut==='retard'?'badge-cancelled':'badge-pending') }}">{{ $c->statut==='solde'?'Soldé':($c->statut==='retard'?'Retard':'En cours') }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if($show['factures'] && $factures->isNotEmpty())
    <div class="title">Factures</div>
    <table class="data">
        <thead><tr><th>N°</th><th>Émission</th><th style="text-align:right;">Total</th><th style="text-align:center;">Statut</th></tr></thead>
        <tbody>
            @foreach($factures as $f)
            <tr>
                <td>{{ $f->numero }}</td>
                <td class="date">{{ $f->date_emission->format('d/m/Y') }}</td>
                <td class="amount">{{ number_format($f->total_ttc,0,',',' ') }} F</td>
                <td style="text-align:center;"><span class="badge {{ $f->estPayee?'badge-paid':'badge-pending' }}">{{ $f->estPayee?'Payée':'En attente' }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if($show['commandes'] && $commandes->isNotEmpty())
    <div class="title">Commandes en ligne</div>
    <table class="data">
        <thead><tr><th>N°</th><th>Date</th><th style="text-align:right;">Total</th><th style="text-align:center;">Statut</th></tr></thead>
        <tbody>
            @foreach($commandes as $cmd)
            <tr>
                <td>{{ $cmd->numero }}</td>
                <td class="date">{{ $cmd->created_at->format('d/m/Y') }}</td>
                <td class="amount">{{ number_format($cmd->total,0,',',' ') }} F</td>
                <td style="text-align:center;">
                    @php $sl = str_replace('_',' ',$cmd->statut); @endphp
                    <span class="badge {{ $cmd->statut==='livree'?'badge-paid':($cmd->statut==='annulee'||$cmd->statut==='refusee'?'badge-cancelled':'badge-pending') }}">{{ ucfirst($sl) }}</span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="footer">
        Fiche générée le {{ now()->translatedFormat('d F Y à H:i') }} · {{ config('app.name') }}
    </div>
</body>
</html>
