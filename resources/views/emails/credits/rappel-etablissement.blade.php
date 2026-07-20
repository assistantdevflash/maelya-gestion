<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rappel echeance credit demain</title>
</head>
<body style="margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f4f4f7;color:#333;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f7;">
<tr><td align="center" style="padding:32px 16px;">
<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);max-width:600px;">

    @php
        $credit   = $echeance->credit;
        $client   = $credit->client;
        $date     = \Carbon\Carbon::parse($echeance->date_prevue);
    @endphp

    {{-- HEADER --}}
    <tr>
        <td bgcolor="#7c3aed" style="background-color:#7c3aed;background:linear-gradient(135deg,#7c3aed,#6366f1);padding:36px 32px;text-align:center;">
            <div style="width:56px;height:56px;background:rgba(255,255,255,0.2);border-radius:16px;display:inline-block;text-align:center;line-height:56px;margin-bottom:16px;font-size:28px;">📅</div>
            <h1 style="color:#ffffff !important;font-size:22px;font-weight:700;margin:0 0 6px;">Echeance credit demain</h1>
            <p style="color:rgba(255,255,255,0.85) !important;font-size:14px;margin:0;">{{ $client?->nom_complet ?? 'Client' }} — {{ $date->translatedFormat('l d F Y') }}</p>
        </td>
    </tr>

    {{-- BODY --}}
    <tr>
        <td style="padding:32px;">
            <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;margin-bottom:28px;">
                <tr style="background:#f9fafb;">
                    <td style="font-size:11px;font-weight:700;text-transform:uppercase;color:#6b7280;padding:10px 16px;">Client</td>
                    <td style="font-size:11px;font-weight:700;text-transform:uppercase;color:#6b7280;padding:10px 16px;">Echeance</td>
                    <td style="font-size:11px;font-weight:700;text-transform:uppercase;color:#6b7280;padding:10px 16px;">Montant du</td>
                </tr>
                <tr>
                    <td style="font-size:14px;color:#111827;padding:14px 16px;font-weight:600;">
                        {{ $client?->nom_complet ?? 'Client' }}
                        @if($client?->telephone)
                        <br><span style="font-size:12px;color:#9ca3af;">{{ $client->telephone }}</span>
                        @endif
                    </td>
                    <td style="font-size:14px;color:#7c3aed;padding:14px 16px;font-weight:700;">
                        N°{{ $echeance->numero }}/{{ $credit->nb_echeances }}
                    </td>
                    <td style="font-size:18px;color:#dc2626;padding:14px 16px;font-weight:700;">
                        {{ number_format($echeance->montant - $echeance->montant_paye, 0, ',', ' ') }} FCFA
                    </td>
                </tr>
            </table>

            <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;margin-bottom:28px;">
                <tr style="background:#f9fafb;">
                    <td style="font-size:11px;font-weight:700;text-transform:uppercase;color:#6b7280;padding:10px 16px;">Montant total</td>
                    <td style="font-size:11px;font-weight:700;text-transform:uppercase;color:#6b7280;padding:10px 16px;">Deja paye</td>
                    <td style="font-size:11px;font-weight:700;text-transform:uppercase;color:#6b7280;padding:10px 16px;">Reste a payer</td>
                </tr>
                <tr>
                    <td style="font-size:16px;color:#111827;padding:14px 16px;font-weight:700;">{{ number_format($credit->montant_total, 0, ',', ' ') }} FCFA</td>
                    <td style="font-size:16px;color:#059669;padding:14px 16px;font-weight:700;">{{ number_format($credit->montant_total - $credit->reste_a_payer, 0, ',', ' ') }} FCFA</td>
                    <td style="font-size:16px;color:#dc2626;padding:14px 16px;font-weight:700;">{{ number_format($credit->reste_a_payer, 0, ',', ' ') }} FCFA</td>
                </tr>
            </table>

            <p style="font-size:13px;color:#374151;margin-bottom:8px;"><strong>Articles :</strong></p>
            @foreach($credit->vente->items as $item)
            <p style="margin:4px 0;font-size:13px;color:#6b7280;">· {{ $item->nom_snapshot }} x{{ $item->quantite }} — {{ number_format($item->sous_total, 0, ',', ' ') }} FCFA</p>
            @endforeach

            @if($credit->notes)
            <div style="background:#f5f3ff;border-radius:10px;padding:14px 16px;margin-top:24px;font-size:13px;color:#4c1d95;">
                <strong>Note :</strong> {{ $credit->notes }}
            </div>
            @endif

            <div style="text-align:center;margin:32px 0 8px;">
                <a href="{{ url('/dashboard/credits/' . $credit->id) }}"
                   style="display:inline-block;padding:14px 40px;background:linear-gradient(135deg,#7c3aed,#6366f1);color:#fff;text-decoration:none;border-radius:10px;font-weight:700;font-size:15px;">
                    Voir le credit
                </a>
            </div>
        </td>
    </tr>

    {{-- FOOTER --}}
    <tr>
        <td style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:20px 32px;text-align:center;font-size:12px;color:#9ca3af;">
            <p style="margin:0;">Ce message a ete envoye automatiquement par Maelya Gestion.</p>
        </td>
    </tr>
</table>
</td></tr>
</table>
</body>
</html>
