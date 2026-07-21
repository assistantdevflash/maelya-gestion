<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"></head>
<body style="font-family:sans-serif;font-size:14px;color:#333;">
    <p>Bonjour {{ $facture->client_prenom ?: ($facture->client->prenom ?? '') }},</p>

    <p>Veuillez trouver ci-joint votre facture <strong>{{ $facture->numero }}</strong>
       du {{ $facture->date_emission->format('d/m/Y') }}, à régler avant le <strong>{{ $facture->date_echeance->format('d/m/Y') }}</strong>.</p>

    <table style="border-collapse:collapse;width:100%;margin:16px 0;">
        <tr style="background:#f3f4f6;">
            <th style="padding:6px 10px;text-align:left;font-size:12px;">Désignation</th>
            <th style="padding:6px 10px;text-align:center;font-size:12px;">Qté</th>
            <th style="padding:6px 10px;text-align:right;font-size:12px;">Prix unitaire</th>
            <th style="padding:6px 10px;text-align:right;font-size:12px;">Total</th>
        </tr>
        @foreach($facture->items as $item)
        <tr style="border-bottom:1px solid #e5e7eb;">
            <td style="padding:6px 10px;">{{ $item->designation }}</td>
            <td style="padding:6px 10px;text-align:center;">{{ $item->quantite }}</td>
            <td style="padding:6px 10px;text-align:right;">{{ number_format($item->prix_unitaire, 0, ',', ' ') }} F</td>
            <td style="padding:6px 10px;text-align:right;">{{ number_format($item->total_ligne, 0, ',', ' ') }} F</td>
        </tr>
        @endforeach
        <tr style="font-weight:bold;">
            <td colspan="3" style="padding:8px 10px;text-align:right;">Total TTC</td>
            <td style="padding:8px 10px;text-align:right;color:#059669;">{{ number_format($facture->total_ttc, 0, ',', ' ') }} FCFA</td>
        </tr>
        @if($facture->montant_paye > 0)
        <tr>
            <td colspan="3" style="padding:6px 10px;text-align:right;color:#059669;">Déjà payé</td>
            <td style="padding:6px 10px;text-align:right;color:#059669;">{{ number_format($facture->montant_paye, 0, ',', ' ') }} FCFA</td>
        </tr>
        @if($facture->resteAPayer > 0)
        <tr style="font-weight:bold;color:#dc2626;">
            <td colspan="3" style="padding:6px 10px;text-align:right;">Reste à payer</td>
            <td style="padding:6px 10px;text-align:right;">{{ number_format($facture->resteAPayer, 0, ',', ' ') }} FCFA</td>
        </tr>
        @endif
        @endif
    </table>

    <p style="margin-top:24px;">Merci de bien vouloir effectuer le règlement avant la date d'échéance.</p>

    <p style="margin-top:24px;color:#6b7280;font-size:12px;">
        {{ $facture->institut?->nom ?? 'Maelya Gestion' }}<br>
        @if($facture->institut?->telephone)Tél : {{ $facture->institut->telephone }} · @endif
        @if($facture->institut?->email){{ $facture->institut->email }}@endif
    </p>
</body>
</html>
