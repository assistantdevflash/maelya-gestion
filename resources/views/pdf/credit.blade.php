<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fiche de credit — {{ $credit->client?->nom_complet ?? 'Client' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2937; line-height: 1.4; padding: 20px 30px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #7c3aed; padding-bottom: 14px; margin-bottom: 16px; }
        .header-left h1 { font-size: 20px; font-weight: 800; color: #7c3aed; margin-bottom: 2px; }
        .header-left .institut { font-size: 12px; color: #6b7280; }
        .header-right { text-align: right; }
        .badge { display: inline-block; padding: 3px 12px; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: uppercase; }
        .badge-en_cours { background: #dbeafe; color: #1d4ed8; }
        .badge-retard { background: #fee2e2; color: #dc2626; }
        .badge-solde { background: #d1fae5; color: #059669; }
        .section { margin-bottom: 16px; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; }
        .section-title { background: #f9fafb; padding: 6px 12px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; border-bottom: 1px solid #e5e7eb; }
        .section-body { padding: 10px 12px; }
        .grid { display: flex; gap: 12px; }
        .grid-2 > * { flex: 1; }
        .grid-3 > * { flex: 1; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 9px; text-transform: uppercase; color: #6b7280; padding: 5px 8px; background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
        td { padding: 5px 8px; border-bottom: 1px solid #f3f4f6; font-size: 10px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: 'DejaVu Sans Mono', monospace; font-size: 10px; }
        .font-bold { font-weight: 700; }
        .text-success { color: #059669; }
        .text-danger { color: #dc2626; }
        .text-muted { color: #9ca3af; }
        .progress-bar { height: 6px; background: #e5e7eb; border-radius: 999px; overflow: hidden; margin: 6px 0; }
        .progress-fill { height: 100%; border-radius: 999px; }
        .big-number { font-size: 16px; font-weight: 800; }
        .footer { margin-top: 24px; padding-top: 10px; border-top: 1px solid #e5e7eb; text-align: center; font-size: 9px; color: #9ca3af; }
        .row-retard { background: #fef2f2; }
    </style>
</head>
<body>

    {{-- En-tête --}}
    <div class="header">
        <div class="header-left">
            <h1>Fiche de credit</h1>
            <p class="institut">{{ $institut?->nom ?? 'Maelya Gestion' }}{{ $institut?->ville ? ' — ' . $institut->ville : '' }}</p>
            <p style="font-size:9px;color:#9ca3af;">N° credit : {{ $credit->id }}</p>
        </div>
        <div class="header-right">
            <span class="badge badge-{{ $credit->statut }}">
                @if($credit->statut === 'solde') Solde
                @elseif($credit->statut === 'retard') En retard
                @else En cours
                @endif
            </span>
            <p style="font-size:9px;color:#9ca3af;margin-top:4px;">Genere le {{ now()->format('d/m/Y a H:i') }}</p>
        </div>
    </div>

    {{-- Client + Vente --}}
    <div class="grid grid-2" style="margin-bottom:12px;">
        <div class="section">
            <div class="section-title">Client</div>
            <div class="section-body">
                <p class="font-bold" style="font-size:13px;">{{ $credit->client?->nom_complet ?? '—' }}</p>
                @if($credit->client?->telephone)
                <p style="font-size:10px;">Tel : {{ $credit->client->telephone }}</p>
                @endif
                @if($credit->client?->adresse)
                <p style="font-size:10px;">Adresse : {{ $credit->client->adresse }}</p>
                @endif
                @if($credit->client?->piece_identite)
                <p style="font-size:10px;">Piece ID : {{ $credit->client->piece_identite }}</p>
                @endif
            </div>
        </div>
        <div class="section">
            <div class="section-title">Vente liee</div>
            <div class="section-body">
                <p style="font-size:10px;">Vente #{{ $credit->vente?->numero ?? substr($credit->vente_id, 0, 8) }}</p>
                <p style="font-size:10px;">Date : {{ \Carbon\Carbon::parse($credit->date_debut)->format('d/m/Y') }}</p>
                <p style="font-size:10px;margin-top:3px;">
                    {{ $credit->nb_echeances }} echeances {{ $credit->frequence === 'mensuelle' ? 'mensuelles' : 'hebdomadaires' }}
                </p>
                @if($credit->date_fin_prevue)
                <p style="font-size:10px;">Fin prevue : {{ \Carbon\Carbon::parse($credit->date_fin_prevue)->format('d/m/Y') }}</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Progression --}}
    <div class="section">
        <div class="section-title">Progression de paiement</div>
        <div class="section-body">
            @php $pct = $credit->montant_total > 0 ? round(($credit->montant_total - $credit->reste_a_payer) * 100 / $credit->montant_total) : 100; @endphp
            <div class="progress-bar">
                <div class="progress-fill" style="width:{{ $pct }}%; background: {{ $credit->statut === 'solde' ? '#059669' : '#7c3aed' }};"></div>
            </div>
            <div class="grid grid-3" style="margin-top:8px;">
                <div>
                    <p style="font-size:9px;color:#6b7280;">MONTANT TOTAL</p>
                    <p class="big-number">{{ number_format($credit->montant_total, 0, ',', ' ') }} FCFA</p>
                </div>
                <div>
                    <p style="font-size:9px;color:#6b7280;">DEJA PAYE</p>
                    <p class="big-number text-success">{{ number_format($credit->montant_total - $credit->reste_a_payer, 0, ',', ' ') }} FCFA</p>
                </div>
                <div>
                    <p style="font-size:9px;color:#6b7280;">RESTE A PAYER</p>
                    <p class="big-number {{ $credit->reste_a_payer > 0 ? 'text-danger' : 'text-muted' }}">
                        {{ number_format($credit->reste_a_payer, 0, ',', ' ') }} FCFA
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Articles --}}
    <div class="section">
        <div class="section-title">Articles vendus</div>
        <table>
            <thead><tr><th>Designation</th><th class="text-center">Qte</th><th class="text-right">Prix unitaire</th><th class="text-right">Total</th></tr></thead>
            <tbody>
                @foreach($credit->vente->items as $item)
                <tr>
                    <td>{{ $item->nom_snapshot }}</td>
                    <td class="text-center">x{{ $item->quantite }}</td>
                    <td class="text-right font-mono">{{ number_format($item->prix_unitaire, 0, ',', ' ') }} F</td>
                    <td class="text-right font-mono font-bold">{{ number_format($item->sous_total, 0, ',', ' ') }} F</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right font-bold">Total vente</td>
                    <td class="text-right font-mono font-bold" style="font-size:13px;">{{ number_format($credit->montant_total, 0, ',', ' ') }} FCFA</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Echeancier --}}
    <div class="section">
        <div class="section-title">Echeancier</div>
        <table>
            <thead><tr><th>N°</th><th>Date prevue</th><th class="text-right">Montant</th><th class="text-right">Paye</th><th class="text-center">Statut</th></tr></thead>
            <tbody>
                @foreach($credit->echeances as $echeance)
                <tr class="{{ $echeance->statut === 'retard' ? 'row-retard' : '' }}">
                    <td class="font-mono">{{ $echeance->numero }}/{{ $credit->nb_echeances }}</td>
                    <td>{{ \Carbon\Carbon::parse($echeance->date_prevue)->format('d/m/Y') }}</td>
                    <td class="text-right font-mono">{{ number_format($echeance->montant, 0, ',', ' ') }} F</td>
                    <td class="text-right font-mono {{ $echeance->montant_paye >= $echeance->montant ? 'text-success' : '' }}">
                        {{ $echeance->montant_paye > 0 ? number_format($echeance->montant_paye, 0, ',', ' ') . ' F' : '—' }}
                    </td>
                    <td class="text-center">
                        @if($echeance->statut === 'payee')
                            <span style="color:#059669;font-weight:700;">Payee</span>
                        @elseif($echeance->statut === 'retard')
                            <span style="color:#dc2626;font-weight:700;">Retard</span>
                        @else
                            <span style="color:#6b7280;">En attente</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Historique paiements --}}
    @if($credit->paiements->isNotEmpty())
    <div class="section">
        <div class="section-title">Historique des paiements</div>
        <table>
            <thead><tr><th>Date</th><th class="text-right">Montant</th><th>Mode</th><th>Encaissé par</th></tr></thead>
            <tbody>
                @foreach($credit->paiements as $p)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($p->created_at)->format('d/m/Y H:i') }}</td>
                    <td class="text-right font-mono font-bold">{{ number_format($p->montant, 0, ',', ' ') }} F</td>
                    <td>
                        @if($p->mode_paiement === 'cash') Especes
                        @elseif($p->mode_paiement === 'mobile_money') Mobile Money
                        @else Carte bancaire
                        @endif
                    </td>
                    <td>{{ $p->encaisseur?->prenom ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <p>{{ $institut?->nom ?? 'Maelya Gestion' }}{{ $institut?->ville ? ' — ' . $institut->ville : '' }} | Document genere le {{ now()->format('d/m/Y a H:i') }}</p>
    </div>

</body>
</html>
