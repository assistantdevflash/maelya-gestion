<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $titre ?? 'Maëlya Gestion' }}</title>
    <style>
        /* Reset */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f3f4f6; color: #1f2937; -webkit-font-smoothing: antialiased; }

        /* Enveloppe */
        .wrapper { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 8px 32px rgba(17, 24, 39, 0.08); }

        /* ── Header ── */
        .header { background: linear-gradient(135deg, #7c3aed 0%, #9333ea 45%, #ec4899 100%); padding: 40px 36px 36px; text-align: center; position: relative; }
        .header::after { content: ''; position: absolute; inset: 0; background: radial-gradient(circle at 85% 15%, rgba(255,255,255,0.12) 0%, transparent 50%); }
        .header-logo { margin-bottom: 18px; }
        .header-logo img { width: 64px; height: 64px; border-radius: 18px; }
        .header h1 { color: #ffffff !important; font-size: 24px; font-weight: 800; line-height: 1.3; letter-spacing: -0.01em; }
        .header p { color: rgba(255,255,255,0.9) !important; font-size: 14px; margin-top: 8px; line-height: 1.5; }

        /* ── Corps ── */
        .body { padding: 32px 36px; }

        /* Sections */
        .section-title { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; color: #7c3aed; margin-bottom: 12px; }
        .card { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 14px; padding: 20px; margin-bottom: 20px; }
        .row { display: flex; align-items: flex-start; gap: 8px; margin-bottom: 10px; }
        .row:last-child { margin-bottom: 0; }
        .label { font-size: 12px; color: #6b7280; font-weight: 600; min-width: 140px; flex-shrink: 0; }
        .value { font-size: 14px; color: #111827; font-weight: 600; }
        .divider { border: none; border-top: 1px solid #e5e7eb; margin: 24px 0; }

        /* Boutons */
        .cta { text-align: center; margin: 28px 0 8px; }
        .btn { display: inline-block; padding: 14px 36px; background: linear-gradient(135deg, #7c3aed, #ec4899); color: #ffffff !important; text-decoration: none; border-radius: 12px; font-weight: 700; font-size: 15px; box-shadow: 0 4px 16px rgba(124, 58, 237, 0.35); }
        .btn-secondary { display: inline-block; padding: 12px 28px; background: #f3f4f6; color: #4b5563 !important; text-decoration: none; border-radius: 12px; font-weight: 600; font-size: 13px; border: 1px solid #e5e7eb; }

        /* Badges */
        .badge { display: inline-block; padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 700; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger  { background: #fee2e2; color: #991b1b; }
        .badge-info    { background: #ede9fe; color: #5b21b6; }

        /* Encadrés spéciaux */
        .alert { border-radius: 12px; padding: 14px 16px; margin-bottom: 24px; font-size: 14px; }
        .alert-info    { background: #f5f3ff; border: 1px solid #ddd6fe; color: #5b21b6; }
        .alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .alert-warning { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }
        .alert-danger  { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }

        /* Montants */
        .amount { font-size: 28px; font-weight: 800; background: linear-gradient(135deg, #7c3aed, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }

        /* ── Footer ── */
        .footer { background: #f9fafb; border-top: 1px solid #e5e7eb; padding: 24px 36px; text-align: center; font-size: 12px; color: #9ca3af; }
        .footer-logo { font-size: 14px; font-weight: 800; color: #6b7280; margin-bottom: 6px; }
        .footer p { line-height: 1.6; }

        @media (max-width: 480px) {
            .header { padding: 28px 20px; }
            .body { padding: 24px 20px; }
            .row { flex-direction: column; gap: 2px; }
            .label { min-width: 0; }
        }
    </style>
</head>
<body>
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;">
<tr><td align="center" style="padding:32px 16px;">
<table class="wrapper" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 8px 32px rgba(17,24,39,0.08);max-width:600px;">

    {{-- ══ HEADER ══ --}}
    <tr>
        <td bgcolor="#7c3aed" style="background-color:#7c3aed;background:linear-gradient(135deg,#7c3aed 0%,#9333ea 45%,#ec4899 100%);padding:40px 36px 36px;text-align:center;">
            <div style="margin-bottom:18px;">
                <img src="{{ url('/icons/icon-192.png?v=4') }}" alt="Maëlya" width="64" height="64" style="width:64px;height:64px;border-radius:18px;display:inline-block;">
            </div>
            <h1 style="color:#ffffff !important;font-size:24px;font-weight:800;line-height:1.3;margin:0;">{{ $titre ?? 'Maëlya Gestion' }}</h1>
            @if(!empty($sousTitre))
            <p style="color:rgba(255,255,255,0.9) !important;font-size:14px;margin:8px 0 0;line-height:1.5;">{{ $sousTitre }}</p>
            @endif
        </td>
    </tr>

    {{-- ══ CORPS ══ --}}
    <tr>
        <td style="padding:32px 36px;">
            {{ $slot }}
        </td>
    </tr>

    {{-- ══ FOOTER ══ --}}
    <tr>
        <td bgcolor="#f9fafb" style="background-color:#f9fafb;padding:24px 36px;text-align:center;">
            <div class="footer-logo" style="font-size:14px;font-weight:800;color:#6b7280;margin-bottom:6px;">Maëlya Gestion</div>
            <p style="color:#9ca3af;font-size:12px;line-height:1.6;">
                Cet email a été envoyé automatiquement par <a href="{{ url('/') }}" style="color:#7c3aed;text-decoration:none;font-weight:600;">{{ config('app.name') }}</a>.<br>
                © {{ date('Y') }} Maëlya Gestion — Côte d'Ivoire 🇨🇮
            </p>
        </td>
    </tr>

</table>
</td></tr>
</table>
</body>
</html>
