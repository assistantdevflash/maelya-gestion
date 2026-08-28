<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votre code de vérification</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f4f4f7; color: #333; }
        .wrapper { max-width: 520px; margin: 32px auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #9333ea 0%, #ec4899 100%); padding: 32px; text-align: center; }
        .header-logo { font-size: 26px; font-weight: 900; color: #fff; letter-spacing: -0.5px; }
        .header-logo span { opacity: 0.8; font-weight: 400; }
        .header-sub { color: rgba(255,255,255,0.85); font-size: 13px; margin-top: 4px; }
        .body { padding: 36px 32px; text-align: center; }
        .greeting { font-size: 15px; color: #374151; margin-bottom: 8px; }
        .desc { font-size: 14px; color: #6b7280; line-height: 1.6; margin-bottom: 32px; }
        .code-box { display: inline-block; background: #f9f5ff; border: 2px solid #d8b4fe; border-radius: 16px; padding: 20px 40px; margin-bottom: 24px; }
        .code { font-size: 48px; font-weight: 900; letter-spacing: 12px; color: #9333ea; font-family: 'Courier New', monospace; }
        .code-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; color: #a78bfa; margin-top: 6px; }
        .expiry { font-size: 13px; color: #f59e0b; background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 10px 16px; margin-bottom: 28px; display: inline-block; }
        .warning { font-size: 12px; color: #9ca3af; line-height: 1.6; }
        .footer { background: #f9fafb; padding: 20px 32px; text-align: center; border-top: 1px solid #f3f4f6; }
        .footer p { font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <div class="header-logo">Maëlya<span> Gestion</span></div>
        <div class="header-sub">Vérification de votre adresse email</div>
    </div>
    <div class="body">
        <p class="greeting">Bonjour {{ $user->prenom ?? $user->name }},</p>
        <p class="desc">
            Voici votre code de vérification pour confirmer votre adresse email.<br>
            Saisissez-le dans l'application pour valider votre compte.
        </p>

        <div class="code-box">
            <div class="code">{{ $code }}</div>
            <div class="code-label">Code de vérification</div>
        </div>

        <br>
        <div class="expiry">⏱ Ce code expire dans 15 minutes</div>

        <p class="warning">
            Si vous n'avez pas demandé ce code, ignorez cet email.<br>
            Ne partagez jamais ce code avec quiconque.
        </p>
    </div>
    <div class="footer">
        <p>© {{ date('Y') }} Maëlya Gestion — Cet email a été envoyé automatiquement.</p>
    </div>
</div>
</body>
</html>
