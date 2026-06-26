<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Crédit #{{ substr($credit->id, 0, 8) }} — {{ $credit->client?->nom_complet ?? 'Client' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, sans-serif; color: #1f2937; font-size: 13px; line-height: 1.5; max-width: 800px; margin: 0 auto; padding: 20px; }
        @media print {
            body { padding: 10px; }
            .no-print { display: none !important; }
        }
        .header { display: flex; justify-content: space-between; align-items: start; border-bottom: 2px solid #e5e7eb; padding-bottom: 16px; margin-bottom: 20px; }
        .header h1 { font-size: 20px; font-weight: 800; }
        .header .institut { font-size: 14px; color: #6b7280; }
        .statut { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; }
        .statut-en_cours { background: #dbeafe; color: #1d4ed8; }
        .statut-retard { background: #fee2e2; color: #dc2626; }
        .statut-solde { background: #d1fae5; color: #059669; }
        .grid { display: grid; gap: 12px; }
        .grid-2 { grid-template-columns: 1fr 1fr; }
        .grid-3 { grid-template-columns: 1fr 1fr 1fr; }
        .card { border: 1px solid #e5e7eb; border-radius: 10px; padding: 14px; margin-bottom: 12px; }
        .card h2 { font-size: 12px; text-transform: uppercase; letter-spacing: .05em; color: #6b7280; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; font-size: 10px; text-transform: uppercase; color: #6b7280; padding: 8px 10px; background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
        td { padding: 8px 10px; border-bottom: 1px solid #f3f4f6; font-size: 12px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: 'SF Mono', 'Menlo', monospace; font-size: 11px; }
        .font-bold { font-weight: 700; }
        .text-success { color: #059669; }
        .text-danger { color: #dc2626; }
        .text-muted { color: #9ca3af; }
        .progress-bar { height: 8px; background: #e5e7eb; border-radius: 999px; overflow: hidden; margin: 8px 0; }
        .progress-fill { height: 100%; border-radius: 999px; }
        .footer { margin-top: 30px; padding-top: 12px; border-top: 1px solid #e5e7eb; text-align: center; font-size: 10px; color: #9ca3af; }
        .actions { display: flex; gap: 10px; margin-bottom: 16px; }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; font-size: 12px; font-weight: 600; text-decoration: none; cursor: pointer; border: 1px solid #d1d5db; background: #fff; color: #374151; }
        .btn-primary { background: #4f46e5; color: #fff; border-color: #4f46e5; }
        .btn-wa { background: #25d366; color: #fff; border-color: #25d366; }
    </style>
</head>
<body>

    <div class="actions no-print">
        <button class="btn" onclick="window.print()">🖨️ Imprimer</button>
        @if($waUrl)
        <a href="{{ $waUrl }}" target="_blank" class="btn btn-wa">💬 Partager WhatsApp</a>
        @endif
        <a href="{{ route('dashboard.credits.show', $credit) }}" class="btn">← Retour</a>
    </div>

    <div class="header">
        <div>
            <h1>Fiche de crédit</h1>
            <p class="institut">{{ $institut?->nom ?? 'Maëlya Gestion' }}</p>
            <p style="font-size:11px;color:#9ca3af;">N° crédit : {{ $credit->id }}</p>
        </div>
        <div style="text-align:right;">
            <span class="statut statut-{{ $credit->statut }}">
                @if($credit->statut === 'solde') Soldé
                @elseif($credit->statut === 'retard') En retard
                @else En cours
                @endif
            </span>
            <p style="font-size:10px;color:#9ca3af;margin-top:4px;">Généré le {{ now()->format('d/m/Y à H:i') }}</p>
        </div>
    </div>

    {{-- Client & Vente --}}
    <div class="grid grid-2" style="margin-bottom:12px;">
        <div class="card">
            <h2>👤 Client</h2>
            <p class="font-bold" style="font-size:14px;">{{ $credit->client?->nom_complet ?? '—' }}</p>
            @if($credit->client?->telephone)
            <p style="font-size:12px;">📞 {{ $credit->client->telephone }}</p>
            @endif
            @if($credit->client?->adresse)
            <p style="font-size:12px;">📍 {{ $credit->client->adresse }}</p>
            @endif
            @if($credit->client?->piece_identite)
            <p style="font-size:12px;">🪪 {{ $credit->client->piece_identite }}</p>
            @endif
        </div>
        <div class="card">
            <h2>📋 Vente liée</h2>
            <p style="font-size:12px;">Vente #{{ $credit->vente?->numero ?? substr($credit->vente_id, 0, 8) }}</p>
            <p style="font-size:12px;">Date : {{ \Carbon\Carbon::parse($credit->date_debut)->format('d/m/Y') }}</p>
            <p style="font-size:12px;margin-top:4px;">
                {{ $credit->nb_echeances }} échéances {{ $credit->frequence === 'mensuelle' ? 'mensuelles' : 'hebdomadaires' }}
            </p>
            @if($credit->date_fin_prevue)
            <p style="font-size:12px;">Fin prévue : {{ \Carbon\Carbon::parse($credit->date_fin_prevue)->format('d/m/Y') }}</p>
            @endif
        </div>
    </div>

    {{-- Progression --}}
    <div class="card">
        <h2>💰 Progression de paiement</h2>
        @php $pct = $credit->montant_total > 0 ? round(($credit->montant_total - $credit->reste_a_payer) * 100 / $credit->montant_total) : 100; @endphp
        <div class="progress-bar">
            <div class="progress-fill" style="width:{{ $pct }}%; background: {{ $credit->statut === 'solde' ? '#059669' : '#4f46e5' }};"></div>
        </div>
        <div class="grid grid-3" style="margin-top:8px;">
            <div>
                <p style="font-size:10px;color:#6b7280;">MONTANT TOTAL</p>
                <p class="font-bold" style="font-size:16px;">{{ number_format($credit->montant_total, 0, ',', ' ') }} F</p>
            </div>
            <div>
                <p style="font-size:10px;color:#6b7280;">DÉJÀ PAYÉ</p>
                <p class="font-bold text-success" style="font-size:16px;">{{ number_format($credit->montant_total - $credit->reste_a_payer, 0, ',', ' ') }} F</p>
            </div>
            <div>
                <p style="font-size:10px;color:#6b7280;">RESTE À PAYER</p>
                <p class="font-bold {{ $credit->reste_a_payer > 0 ? 'text-danger' : 'text-muted' }}" style="font-size:16px;">{{ number_format($credit->reste_a_payer, 0, ',', ' ') }} F</p>
            </div>
        </div>
    </div>

    {{-- Articles --}}
    <div class="card">
        <h2>🛍️ Articles vendus</h2>
        <table>
            <thead><tr><th>Article</th><th class="text-center">Qté</th><th class="text-right">Prix</th><th class="text-right">Total</th></tr></thead>
            <tbody>
                @foreach($credit->vente->items as $item)
                <tr>
                    <td>{{ $item->nom_snapshot }}</td>
                    <td class="text-center">×{{ $item->quantite }}</td>
                    <td class="text-right font-mono">{{ number_format($item->prix_unitaire, 0, ',', ' ') }} F</td>
                    <td class="text-right font-mono font-bold">{{ number_format($item->sous_total, 0, ',', ' ') }} F</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right font-bold">Total vente</td>
                    <td class="text-right font-mono font-bold" style="font-size:14px;">{{ number_format($credit->montant_total, 0, ',', ' ') }} F</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Échéancier --}}
    <div class="card">
        <h2>📅 Échéancier</h2>
        <table>
            <thead><tr><th>N°</th><th>Date prévue</th><th class="text-right">Montant</th><th class="text-right">Payé</th><th class="text-center">Statut</th></tr></thead>
            <tbody>
                @foreach($credit->echeances as $echeance)
                <tr style="{{ $echeance->statut === 'retard' ? 'background:#fef2f2;' : '' }}">
                    <td class="font-mono">{{ $echeance->numero }}/{{ $credit->nb_echeances }}</td>
                    <td>{{ \Carbon\Carbon::parse($echeance->date_prevue)->format('d/m/Y') }}</td>
                    <td class="text-right font-mono">{{ number_format($echeance->montant, 0, ',', ' ') }} F</td>
                    <td class="text-right font-mono {{ $echeance->montant_paye >= $echeance->montant ? 'text-success' : '' }}">
                        {{ $echeance->montant_paye > 0 ? number_format($echeance->montant_paye, 0, ',', ' ') . ' F' : '—' }}
                    </td>
                    <td class="text-center">
                        @if($echeance->statut === 'payee')
                            <span style="color:#059669;font-weight:700;">Payée</span>
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
    <div class="card">
        <h2>🧾 Historique des paiements</h2>
        <table>
            <thead><tr><th>Date</th><th class="text-right">Montant</th><th>Mode</th><th>Encaissé par</th></tr></thead>
            <tbody>
                @foreach($credit->paiements as $p)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($p->created_at)->format('d/m/Y H:i') }}</td>
                    <td class="text-right font-mono font-bold">{{ number_format($p->montant, 0, ',', ' ') }} F</td>
                    <td>
                        @if($p->mode_paiement === 'cash') Espèces
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
        <p>{{ $institut?->nom ?? 'Maëlya Gestion' }}{{ $institut?->ville ? ' — ' . $institut->ville : '' }}</p>
        <p>Document généré le {{ now()->format('d/m/Y à H:i') }}</p>
    </div>

</body>
</html>
