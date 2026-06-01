<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 0; }
        body { margin: 0; font-family: DejaVu Sans, sans-serif; width: 240px; height: 150px; }
        .card { width: 240px; height: 150px; padding: 8px; box-sizing: border-box; position: relative; background: #fff; }
        .header { background: linear-gradient(90deg, #ec4899, #f43f5e); color: #fff; padding: 6px 8px; border-radius: 6px 6px 0 0; text-align: center; margin: -8px -8px 4px -8px; }
        .header .nom { font-size: 9px; opacity: .9; margin-bottom: 1px; }
        .header .titre { font-size: 11px; font-weight: bold; }
        .row { display: flex; align-items: center; gap: 6px; margin-top: 4px; }
        .info { flex: 1; padding-left: 4px; }
        .client { font-size: 10px; font-weight: bold; color: #111; }
        .solde-label { font-size: 7px; color: #888; text-transform: uppercase; letter-spacing: .04em; }
        .solde { font-size: 18px; font-weight: bold; color: #ec4899; line-height: 1; margin: 2px 0; }
        .points-suffix { font-size: 8px; color: #555; }
        .qr { width: 80px; height: 80px; }
        .footer { position: absolute; bottom: 4px; left: 8px; right: 8px; font-size: 6.5px; color: #999; text-align: center; }
    </style>
</head>
<body>
<div class="card">
    <div class="header">
        @if($institut)
            <div class="nom">{{ $institut->nom }}</div>
        @endif
        <div class="titre">CARTE FIDÉLITÉ</div>
    </div>
    <div class="row">
        <img class="qr" src="{{ $qrUrl }}" alt="QR">
        <div class="info">
            <div class="client">{{ $client->prenom }} {{ $client->nom }}</div>
            <div class="solde-label">Solde</div>
            <div class="solde">{{ $client->points_fidelite }} <span class="points-suffix">pts</span></div>
        </div>
    </div>
    <div class="footer">Présentez ce QR à votre prochaine visite ✨</div>
</div>
</body>
</html>
