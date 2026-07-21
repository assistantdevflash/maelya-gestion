<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"></head>
<body style="font-family:sans-serif;font-size:14px;color:#333;">
    <p>Bonjour {{ $devis->client_prenom ?: ($devis->client->prenom ?? '') }},</p>

    <p>Veuillez trouver ci-joint votre devis <strong>{{ $devis->numero }}</strong>
       du {{ $devis->date_creation->format('d/m/Y') }}, valable jusqu'au <strong>{{ $devis->date_expiration->format('d/m/Y') }}</strong>.</p>

    <table style="border-collapse:collapse;width:100%;margin:16px 0;">
        <tr style="background:#f3f4f6;">
            <th style="padding:6px 10px;text-align:left;font-size:12px;">Désignation</th>
            <th style="padding:6px 10px;text-align:center;font-size:12px;">Qté</th>
            <th style="padding:6px 10px;text-align:right;font-size:12px;">Prix unitaire</th>
            <th style="padding:6px 10px;text-align:right;font-size:12px;">Total</th>
        </tr>
        @foreach($devis->items as $item)
        <tr style="border-bottom:1px solid #e5e7eb;">
            <td style="padding:6px 10px;">{{ $item->designation }}</td>
            <td style="padding:6px 10px;text-align:center;">{{ $item->quantite }}</td>
            <td style="padding:6px 10px;text-align:right;">{{ number_format($item->prix_unitaire, 0, ',', ' ') }} F</td>
            <td style="padding:6px 10px;text-align:right;">{{ number_format($item->total_ligne, 0, ',', ' ') }} F</td>
        </tr>
        @endforeach
        <tr style="font-weight:bold;">
            <td colspan="3" style="padding:8px 10px;text-align:right;">Total TTC</td>
            <td style="padding:8px 10px;text-align:right;color:#6d28d9;">{{ number_format($devis->total_ttc, 0, ',', ' ') }} FCFA</td>
        </tr>
    </table>

    @if($devis->notes)
    <p style="color:#6b7280;">Note : {{ $devis->notes }}</p>
    @endif

    <p style="margin-top:24px;">Pour toute question, n'hésitez pas à nous contacter.</p>

    <p style="margin-top:24px;color:#6b7280;font-size:12px;">
        {{ $devis->institut?->nom ?? 'Maelya Gestion' }}<br>
        @if($devis->institut?->telephone)Tél : {{ $devis->institut->telephone }} · @endif
        @if($devis->institut?->email){{ $devis->institut->email }}@endif
    </p>
</body>
</html>
