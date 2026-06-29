<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"></head>
<body style="font-family:sans-serif;font-size:14px;color:#333;">
    <p>Bonjour {{ $bon->fournisseur->contact_principal ?: $bon->fournisseur->nom }},</p>

    <p>Veuillez trouver ci-joint notre bon de commande <strong>{{ $bon->numero }}</strong>
       du {{ $bon->date_commande->format('d/m/Y') }}.</p>

    <table style="border-collapse:collapse;width:100%;margin:16px 0;">
        <tr style="background:#f3f4f6;">
            <th style="padding:6px 10px;text-align:left;font-size:12px;">Designation</th>
            <th style="padding:6px 10px;text-align:center;font-size:12px;">Qte</th>
            <th style="padding:6px 10px;text-align:right;font-size:12px;">Prix HT</th>
            <th style="padding:6px 10px;text-align:right;font-size:12px;">Sous-total</th>
        </tr>
        @foreach($bon->lignes as $ligne)
        <tr style="border-bottom:1px solid #e5e7eb;">
            <td style="padding:6px 10px;">{{ $ligne->libelle }}</td>
            <td style="padding:6px 10px;text-align:center;">{{ $ligne->quantite_commandee }}</td>
            <td style="padding:6px 10px;text-align:right;">{{ number_format($ligne->prix_unitaire, 0, ',', ' ') }} F</td>
            <td style="padding:6px 10px;text-align:right;">{{ number_format($ligne->sous_total, 0, ',', ' ') }} F</td>
        </tr>
        @endforeach
        <tr style="font-weight:bold;">
            <td colspan="3" style="padding:8px 10px;text-align:right;">Total HT</td>
            <td style="padding:8px 10px;text-align:right;">{{ number_format($bon->total_ht, 0, ',', ' ') }} FCFA</td>
        </tr>
    </table>

    @if($bon->date_livraison_prevue)
    <p>Livraison prevue : <strong>{{ \Carbon\Carbon::parse($bon->date_livraison_prevue)->format('d/m/Y') }}</strong></p>
    @endif

    @if($bon->notes)
    <p style="color:#6b7280;">Note : {{ $bon->notes }}</p>
    @endif

    <p style="margin-top:24px;color:#6b7280;font-size:12px;">
        {{ $bon->institut?->nom ?? 'Maelya Gestion' }}
        @if($bon->institut?->telephone) — {{ $bon->institut->telephone }} @endif
    </p>
</body>
</html>
