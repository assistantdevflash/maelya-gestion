<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code {{ $code->code }} — Maëlya Gestion</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Courier New', Courier, monospace;
            background: #fff;
            color: #111;
            display: flex;
            justify-content: center;
            padding: 20px;
        }

        .recu {
            width: 280px;
            border: 1px dashed #ccc;
            padding: 16px;
        }

        .header {
            text-align: center;
            border-bottom: 2px dashed #999;
            padding-bottom: 12px;
            margin-bottom: 12px;
        }

        .header h1 {
            font-size: 15px;
            font-family: Arial, sans-serif;
            letter-spacing: 1px;
            color: #9333ea;
        }

        .header p {
            font-size: 10px;
            color: #888;
            margin-top: 2px;
        }

        .code-box {
            text-align: center;
            border: 2px dashed #9333ea;
            border-radius: 6px;
            padding: 12px 8px;
            margin: 12px 0;
        }

        .code-box .code-val {
            display: block;
            font-size: 26px;
            font-weight: bold;
            letter-spacing: 4px;
            color: #9333ea;
        }

        .code-box .code-remise {
            display: block;
            font-size: 20px;
            font-weight: bold;
            margin-top: 4px;
            color: #ec4899;
        }

        .details {
            margin: 10px 0;
            font-size: 11px;
        }

        .details .row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            border-bottom: 1px dotted #ddd;
        }

        .details .row:last-child {
            border-bottom: none;
        }

        .details .lbl { color: #666; }
        .details .val { font-weight: bold; text-align: right; max-width: 55%; }

        .desc {
            margin-top: 10px;
            padding: 6px 8px;
            background: #f5f5f5;
            border-radius: 4px;
            font-size: 10px;
            color: #555;
            text-align: center;
            font-style: italic;
        }

        .footer {
            text-align: center;
            margin-top: 14px;
            padding-top: 10px;
            border-top: 2px dashed #999;
            font-size: 9px;
            color: #aaa;
            line-height: 1.6;
        }

        .print-btn {
            display: block;
            margin: 20px auto 0;
            padding: 8px 20px;
            background: #9333ea;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
            font-family: Arial, sans-serif;
        }

        @media print {
            .print-btn { display: none; }
            body { padding: 0; }
            .recu { border: none; }
        }
    </style>
</head>
<body>
    <div>
        <div class="recu">
            <div class="header">
                <h1>{{ $code->institut?->nom ?? 'Mon Institut' }}</h1>
                <p>Code de réduction</p>
            </div>

            <div class="code-box">
                <span class="code-val">{{ $code->code }}</span>
                <span class="code-remise">
                    {{ $code->type === 'pourcentage'
                        ? '-'.$code->valeur.'%'
                        : '-'.number_format($code->valeur, 0, ',', ' ').' FCFA' }}
                </span>
            </div>

            <div class="details">
                <div class="row">
                    <span class="lbl">Client</span>
                    <span class="val">
                        {{ $code->client ? $code->client->prenom.' '.$code->client->nom : 'Tous les clients' }}
                    </span>
                </div>
                <div class="row">
                    <span class="lbl">Montant minimum</span>
                    <span class="val">
                        {{ $code->montant_minimum ? number_format($code->montant_minimum, 0, ',', ' ').' FCFA' : 'Aucun' }}
                    </span>
                </div>
                <div class="row">
                    <span class="lbl">Valable du</span>
                    <span class="val">{{ $code->date_debut ? $code->date_debut->format('d/m/Y') : '—' }}</span>
                </div>
                <div class="row">
                    <span class="lbl">Valable jusqu'au</span>
                    <span class="val">{{ $code->date_fin ? $code->date_fin->format('d/m/Y') : 'Illimitée' }}</span>
                </div>
                <div class="row">
                    <span class="lbl">Utilisations max.</span>
                    <span class="val">{{ $code->limite_utilisation ?? 'Illimitée' }}</span>
                </div>
            </div>

            @if($code->description)
                <div class="desc">{{ $code->description }}</div>
            @endif

            <div class="footer">
                <p>Présentez ce coupon lors de votre passage en caisse.</p>
                <p>Non cumulable avec d'autres offres.</p>
                <p style="margin-top:4px;">Merci pour votre fidélité ❤</p>
            </div>
        </div>

        <button class="print-btn" onclick="window.print()">🖨 Imprimer</button>
    </div>

    <script>
        window.onload = function () { window.print(); };
    </script>
</body>
</html>
