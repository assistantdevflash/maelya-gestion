<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi de mon crédit — {{ $institut?->nom ?? config('app.name') }}</title>
    <meta name="robots" content="noindex, nofollow">
    @php
        $cp = $institut?->couleur_primaire ?? '#9333ea';
        $cs = $institut?->couleur_secondaire ?? '#ec4899';
        $paye = (int) $credit->montant_total - (int) $credit->reste_a_payer;
        $pct  = $credit->montant_total > 0 ? round($paye / (int) $credit->montant_total * 100) : 0;
    @endphp
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f4f4f7;color:#1f2937;padding:16px;line-height:1.5}
        .wrap{max-width:640px;margin:0 auto}
        .card{background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,.06);overflow:hidden;margin-bottom:16px}
        .head{background:linear-gradient(135deg,{{ $cp }},{{ $cs }});color:#fff;padding:26px 20px;text-align:center}
        .head h1{font-size:19px;font-weight:700;margin-bottom:4px}
        .head p{font-size:12.5px;opacity:.9}
        .body{padding:20px}
        .kpi{display:flex;gap:12px;margin-bottom:18px}
        .kpi>div{flex:1;background:#f9fafb;border:1px solid #eef0f3;border-radius:12px;padding:12px}
        .kpi .lbl{font-size:10.5px;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;font-weight:700}
        .kpi .val{font-size:17px;font-weight:700;margin-top:3px}
        .reste{color:#dc2626}
        .bar{height:8px;background:#e5e7eb;border-radius:999px;overflow:hidden;margin-bottom:6px}
        .bar>span{display:block;height:100%;background:linear-gradient(90deg,{{ $cp }},{{ $cs }})}
        h2{font-size:13px;font-weight:700;margin-bottom:10px;color:#111827}
        table{width:100%;border-collapse:collapse;font-size:12.5px}
        th{font-size:10.5px;text-transform:uppercase;letter-spacing:.05em;color:#6b7280;text-align:left;padding:8px 6px;border-bottom:2px solid #eef0f3}
        td{padding:9px 6px;border-bottom:1px solid #f3f4f6}
        .r{text-align:right}
        .badge{display:inline-block;padding:2px 8px;border-radius:999px;font-size:10.5px;font-weight:700}
        .b-payee{background:#d1fae5;color:#065f46}
        .b-retard{background:#fee2e2;color:#991b1b}
        .b-attente{background:#fef3c7;color:#92400e}
        .btn{display:block;text-align:center;background:{{ $cp }};color:#fff;text-decoration:none;font-weight:600;font-size:13.5px;padding:12px;border-radius:10px}
        .foot{text-align:center;font-size:11px;color:#9ca3af;padding:8px 0 24px}
    </style>
</head>
<body>
<div class="wrap">

    <div class="card">
        <div class="head">
            <h1>Suivi de mon crédit</h1>
            <p>{{ $institut?->nom ?? config('app.name') }}</p>
        </div>
        <div class="body">
            <p style="font-size:14px;font-weight:600;margin-bottom:14px;">
                Bonjour {{ $credit->client?->nom_complet ?? 'cher client' }},
            </p>

            <div class="kpi">
                <div>
                    <div class="lbl">Total</div>
                    <div class="val">{{ number_format((int) $credit->montant_total, 0, ',', ' ') }} F</div>
                </div>
                <div>
                    <div class="lbl">Déjà payé</div>
                    <div class="val" style="color:#059669">{{ number_format($paye, 0, ',', ' ') }} F</div>
                </div>
                <div>
                    <div class="lbl">Reste</div>
                    <div class="val reste">{{ number_format((int) $credit->reste_a_payer, 0, ',', ' ') }} F</div>
                </div>
            </div>

            <div class="bar"><span style="width:{{ $pct }}%"></span></div>
            <p style="font-size:11.5px;color:#6b7280;margin-bottom:18px;">{{ $pct }} % réglé
                @if($credit->date_fin_prevue) · fin prévue le {{ $credit->date_fin_prevue->format('d/m/Y') }}@endif
            </p>

            @if($credit->statut === 'solde')
                <div style="background:#ecfdf5;border:1px solid #a7f3d0;border-radius:12px;padding:14px;text-align:center;font-weight:600;color:#065f46;margin-bottom:16px;">
                    ✅ Votre crédit est entièrement soldé. Merci !
                </div>
            @endif

            <h2>Échéancier</h2>
            <table>
                <thead>
                    <tr><th>N°</th><th>Date prévue</th><th class="r">Montant</th><th class="r">Statut</th></tr>
                </thead>
                <tbody>
                @foreach($credit->echeances as $e)
                    <tr>
                        <td>{{ $e->numero }}</td>
                        <td>{{ $e->date_prevue?->format('d/m/Y') }}</td>
                        <td class="r">{{ number_format((int) $e->montant, 0, ',', ' ') }} F</td>
                        <td class="r">
                            @if($e->statut === 'payee')
                                <span class="badge b-payee">Payée</span>
                            @elseif($e->statut === 'retard')
                                <span class="badge b-retard">En retard</span>
                            @elseif($e->montant_paye > 0)
                                <span class="badge b-attente">Partiel</span>
                            @else
                                <span class="badge b-attente">À venir</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            @if($credit->paiements->isNotEmpty())
            <h2 style="margin-top:20px;">Mes paiements</h2>
            <table>
                <thead>
                    <tr><th>Date</th><th>Mode</th><th class="r">Montant</th></tr>
                </thead>
                <tbody>
                @foreach($credit->paiements as $p)
                    <tr>
                        <td>{{ $p->created_at?->format('d/m/Y') }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $p->mode_paiement)) }}</td>
                        <td class="r" style="font-weight:600">{{ number_format((int) $p->montant, 0, ',', ' ') }} F</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @endif

            <div style="margin-top:20px;">
                <a class="btn" href="{{ $urlFiche }}">Télécharger ma fiche (PDF)</a>
            </div>
        </div>
    </div>

    <p class="foot">
        {{ $institut?->nom ?? config('app.name') }}
        @if($institut?->telephone) · {{ $institut->telephone }}@endif
        <br>Page sécurisée — ne partagez pas ce lien.
    </p>
</div>
</body>
</html>
