<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 0; }
        html, body { margin: 0; padding: 0; }
        body { font-family: DejaVu Sans, sans-serif; width: 240px; height: 150px; color: #1f2937; }

        .card { width: 240px; height: 150px; position: relative; }

        .header {
            background-color: #ec4899;
            color: #fff;
            padding: 6px 10px;
            text-align: center;
        }
        .header .nom    { font-size: 9px;  letter-spacing: .04em; margin: 0; }
        .header .titre  { font-size: 11px; font-weight: bold; margin: 1px 0 0 0; letter-spacing: .08em; }

        .body {
            padding: 6px 8px 4px 8px;
        }
        .body table { width: 100%; border-collapse: collapse; }
        .body td.qr-cell { width: 78px; vertical-align: middle; }
        .body td.info-cell { vertical-align: middle; padding-left: 8px; }

        .qr { width: 76px; height: 76px; display: block; }
        .qr-fallback {
            width: 76px; height: 76px;
            border: 1px dashed #d1d5db;
            font-size: 7px; color: #9ca3af;
            text-align: center;
            line-height: 76px;
        }

        .client {
            font-size: 11px; font-weight: bold; color: #111827;
            margin: 0 0 3px 0;
        }
        .solde-label {
            font-size: 6.5px; color: #6b7280;
            text-transform: uppercase; letter-spacing: .08em;
            margin: 0;
        }
        .solde {
            font-size: 22px; font-weight: bold; color: #ec4899;
            line-height: 1; margin: 1px 0 0 0;
        }
        .solde .pts { font-size: 8px; color: #6b7280; font-weight: normal; }

        .footer {
            position: absolute;
            bottom: 3px; left: 0; right: 0;
            font-size: 6px; color: #9ca3af;
            text-align: center;
        }
        .footer .url { color: #ec4899; }
    </style>
</head>
<body>
<div class="card">
    <div class="header">
        @if($institut)
            <div class="nom">{{ strtoupper($institut->nom) }}</div>
        @endif
        <div class="titre">CARTE DE FIDÉLITÉ</div>
    </div>

    <div class="body">
        <table>
            <tr>
                <td class="qr-cell">
                    @if($qrBase64)
                        <img class="qr" src="{{ $qrBase64 }}" alt="QR">
                    @else
                        <div class="qr-fallback">QR<br>indisponible</div>
                    @endif
                </td>
                <td class="info-cell">
                    <p class="client">{{ $client->prenom }} {{ $client->nom }}</p>
                    <p class="solde-label">Solde points</p>
                    <p class="solde">{{ (int) $client->points_fidelite }} <span class="pts">pts</span></p>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Présentez ce QR à votre prochaine visite
    </div>
</div>
</body>
</html>
