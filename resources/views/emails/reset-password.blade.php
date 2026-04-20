<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation de mot de passe</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f4f4f7; color: #333; }
        .wrapper { max-width: 600px; margin: 32px auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #9333ea, #ec4899); padding: 36px 32px; text-align: center; }
        .header-icon { width: 64px; height: 64px; background: rgba(255,255,255,0.2); border-radius: 50%; display: inline-block; text-align: center; line-height: 64px; margin-bottom: 16px; font-size: 32px; }
        .header h1 { color: #fff; font-size: 22px; font-weight: 700; line-height: 1.3; }
        .header p { color: rgba(255,255,255,0.85); font-size: 14px; margin-top: 6px; }
        .body { padding: 36px 32px; }
        .intro { font-size: 15px; color: #374151; line-height: 1.6; margin-bottom: 28px; }
        .alert-box { background: #fef3c7; border: 1px solid #fcd34d; border-radius: 10px; padding: 14px 16px; font-size: 14px; color: #92400e; margin-bottom: 24px; }
        .cta { text-align: center; margin: 28px 0; }
        .btn { display: inline-block; padding: 16px 40px; background: linear-gradient(135deg, #9333ea, #ec4899); color: #fff !important; text-decoration: none; border-radius: 12px; font-weight: 700; font-size: 15px; letter-spacing: 0.02em; }
        .expiry { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 10px; padding: 14px 16px; font-size: 13px; color: #6b7280; text-align: center; margin: 20px 0; }
        .expiry strong { color: #374151; }
        .link-fallback { margin-top: 24px; padding: 16px; background: #f9fafb; border-radius: 10px; border: 1px solid #e5e7eb; }
        .link-fallback p { font-size: 12px; color: #6b7280; margin-bottom: 8px; }
        .link-fallback a { font-size: 11px; color: #9333ea; word-break: break-all; }
        .footer { background: #f9fafb; border-top: 1px solid #e5e7eb; padding: 20px 32px; text-align: center; font-size: 12px; color: #9ca3af; }
        .footer a { color: #9333ea; text-decoration: none; }
    </style>
</head>
<body>
<div class="wrapper">

    <div class="header">
        <div class="header-icon">🔐</div>
        <h1>Réinitialisation de mot de passe</h1>
        <p>Une demande de réinitialisation a été effectuée.</p>
    </div>

    <div class="body">
        <p class="intro">
            Vous recevez cet email car nous avons reçu une demande de réinitialisation du mot de passe associé à votre compte.<br><br>
            Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe.
        </p>

        <div class="cta">
            <a href="{{ $url }}" class="btn">🔑 &nbsp;Réinitialiser mon mot de passe</a>
        </div>

        <div class="expiry">
            ⏱ &nbsp;Ce lien expire dans <strong>60 minutes</strong>. Passé ce délai, vous devrez faire une nouvelle demande.
        </div>

        <div class="alert-box">
            🛡 &nbsp;Si vous n'avez pas demandé cette réinitialisation, aucune action n'est requise. Votre mot de passe restera inchangé.
        </div>

        <div class="link-fallback">
            <p>Si le bouton ne fonctionne pas, copiez-collez ce lien dans votre navigateur :</p>
            <a href="{{ $url }}">{{ $url }}</a>
        </div>
    </div>

    <div class="footer">
        Cet email a été envoyé automatiquement par <a href="{{ url('/') }}">{{ config('app.name') }}</a>.
    </div>

</div>
</body>
</html>
