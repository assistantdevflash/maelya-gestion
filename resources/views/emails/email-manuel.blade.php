<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $sujet }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f4f4f7; color: #333; }
        .wrapper { max-width: 600px; margin: 32px auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #9333ea, #ec4899); padding: 36px 32px; text-align: center; }
        .header-icon { width: 56px; height: 56px; background: rgba(255,255,255,0.2); border-radius: 16px; display: inline-block; text-align: center; line-height: 56px; margin-bottom: 16px; font-size: 28px; }
        .header h1 { color: #fff; font-size: 22px; font-weight: 700; line-height: 1.3; }
        .header p { color: rgba(255,255,255,0.85); font-size: 14px; margin-top: 6px; }
        .body { padding: 36px 32px; }
        .greeting { font-size: 16px; font-weight: 600; color: #111827; margin-bottom: 20px; }
        .message { font-size: 15px; color: #374151; line-height: 1.75; }
        .message p { margin-bottom: 12px; }
        .message ul, .message ol { padding-left: 20px; margin-bottom: 12px; }
        .message li { margin-bottom: 4px; }
        .message strong { color: #111827; }
        .message a { color: #9333ea; }
        .divider { border: none; border-top: 1px solid #e5e7eb; margin: 28px 0; }
        .footer { background: #f9fafb; border-top: 1px solid #e5e7eb; padding: 20px 32px; text-align: center; font-size: 12px; color: #9ca3af; }
        .footer a { color: #9333ea; text-decoration: none; }
    </style>
</head>
<body>
<div class="wrapper">

    <div class="header">
        <div class="header-icon">✉️</div>
        <h1>{{ config('app.name') }}</h1>
        <p>Message de votre équipe</p>
    </div>

    <div class="body">
        <p class="greeting">Bonjour {{ $destinataire->prenom }} 👋</p>
        <div class="message">{!! $corps !!}</div>
    </div>

    <div class="footer">
        Cet email vous a été envoyé par l'équipe <a href="{{ url('/') }}">{{ config('app.name') }}</a>.<br>
        © {{ date('Y') }} {{ config('app.name') }} — Tous droits réservés
    </div>
</div>
</body>
</html>
