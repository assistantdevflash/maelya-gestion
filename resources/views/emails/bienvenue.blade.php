<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenue sur Maëlya Gestion</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f4f4f7; color: #333; }
        .wrapper { max-width: 600px; margin: 32px auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }

        /* Header */
        .header { background: linear-gradient(135deg, #9333ea 0%, #ec4899 100%); padding: 40px 32px; text-align: center; }
        .header-logo { font-size: 28px; font-weight: 900; color: #fff; letter-spacing: -0.5px; margin-bottom: 4px; }
        .header-logo span { opacity: 0.85; font-weight: 400; }
        .header-tagline { color: rgba(255,255,255,0.8); font-size: 13px; margin-bottom: 24px; }
        .header-welcome { background: rgba(255,255,255,0.15); border-radius: 12px; padding: 16px 20px; display: inline-block; }
        .header-welcome p { color: #fff; font-size: 18px; font-weight: 700; }
        .header-welcome small { color: rgba(255,255,255,0.85); font-size: 13px; }

        /* Body */
        .body { padding: 36px 32px; }
        .intro { font-size: 15px; color: #374151; line-height: 1.7; margin-bottom: 32px; }
        .intro strong { color: #9333ea; }

        /* Section title */
        .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #9333ea; margin-bottom: 16px; }

        /* Feature grid */
        .features { margin-bottom: 32px; }
        .feature { display: flex; align-items: flex-start; gap: 16px; padding: 14px 0; border-bottom: 1px solid #f3f4f6; }
        .feature:last-child { border-bottom: none; }
        .feature-icon { width: 40px; height: 40px; border-radius: 10px; display: inline-block; text-align: center; line-height: 40px; font-size: 20px; flex-shrink: 0; margin-right: 16px; }
        .feature-icon.purple { background: linear-gradient(135deg, rgba(147,51,234,0.12), rgba(168,85,247,0.18)); }
        .feature-icon.pink   { background: linear-gradient(135deg, rgba(236,72,153,0.12), rgba(244,114,182,0.18)); }
        .feature-icon.blue   { background: linear-gradient(135deg, rgba(59,130,246,0.12), rgba(99,102,241,0.18)); }
        .feature-icon.green  { background: linear-gradient(135deg, rgba(16,185,129,0.12), rgba(52,211,153,0.18)); }
        .feature-icon.orange { background: linear-gradient(135deg, rgba(245,158,11,0.12), rgba(251,191,36,0.18)); }
        .feature-text h4 { font-size: 14px; font-weight: 700; color: #111827; margin-bottom: 3px; }
        .feature-text p  { font-size: 13px; color: #6b7280; line-height: 1.5; }

        /* Steps */
        .steps { background: #faf5ff; border: 1px solid #e9d5ff; border-radius: 12px; padding: 20px; margin-bottom: 28px; }
        .step { display: flex; align-items: flex-start; gap: 14px; margin-bottom: 12px; }
        .step:last-child { margin-bottom: 0; }
        .step-num { width: 24px; height: 24px; background: linear-gradient(135deg, #9333ea, #ec4899); color: #fff; border-radius: 50%; display: inline-block; text-align: center; line-height: 24px; font-size: 12px; font-weight: 800; flex-shrink: 0; margin-top: 1px; margin-right: 14px; }
        .step p { font-size: 13px; color: #374151; line-height: 1.5; }
        .step strong { color: #7e22ce; }

        /* CTA */
        .cta { text-align: center; margin: 28px 0 8px; }
        .btn { display: inline-block; padding: 16px 40px; background: linear-gradient(135deg, #9333ea, #ec4899); color: #fff !important; text-decoration: none; border-radius: 12px; font-weight: 700; font-size: 15px; }

        /* Support */
        .support { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 18px 20px; margin-top: 24px; font-size: 13px; color: #6b7280; line-height: 1.6; }
        .support a { color: #9333ea; text-decoration: none; font-weight: 600; }

        /* Footer */
        .footer { background: #f9fafb; border-top: 1px solid #e5e7eb; padding: 20px 32px; text-align: center; font-size: 12px; color: #9ca3af; }
        .footer a { color: #9333ea; text-decoration: none; }
    </style>
</head>
<body>
<div class="wrapper">

    {{-- Header --}}
    <div class="header">
        <div class="header-logo">Maëlya <span>Gestion</span></div>
        <div class="header-tagline">La plateforme de gestion des établissements</div>
        <div class="header-welcome">
            <p>🎉 Bienvenue, {{ $user->prenom ?: $user->name }} !</p>
            <small>Votre espace est prêt. Commencez dès maintenant.</small>
        </div>
    </div>

    <div class="body">

        <p class="intro">
            Votre compte <strong>{{ config('app.name') }}</strong> vient d'être créé avec succès.<br><br>
            Vous avez désormais accès à une suite d'outils conçus pour les professionnels : gérez vos rendez-vous, vos ventes, vos clients, vos stocks et bien plus — depuis un seul tableau de bord.
        </p>

        {{-- Fonctionnalités --}}
        <div class="section-title">Ce qui vous attend</div>
        <div class="features">
            <div class="feature">
                <div class="feature-icon purple">🛒</div>
                <div class="feature-text">
                    <h4>Caisse enregistreuse</h4>
                    <p>Enregistrez vos ventes en quelques secondes — prestations, produits, paiements mixtes (espèces, carte, mobile money).</p>
                </div>
            </div>                <div class="feature">
                <div class="feature-icon purple">📅</div>
                <div class="feature-text">
                    <h4>Agenda & Rendez-vous</h4>
                    <p>Gérez votre planning, confirmez les RDV et envoyez des rappels automatiques à vos clients par email.</p>
                </div>
            </div>            <div class="feature">
                <div class="feature-icon pink">👥</div>
                <div class="feature-text">
                    <h4>Gestion des clients</h4>
                    <p>Carnet client complet, programme de fidélité avec points, gestion des anniversaires et cadeaux automatiques.</p>
                </div>
            </div>
            <div class="feature">
                <div class="feature-icon blue">📦</div>
                <div class="feature-text">
                    <h4>Gestion des stocks</h4>
                    <p>Suivi des produits en temps réel, alertes de rupture, mouvements d'entrée/sortie, historique complet.</p>
                </div>
            </div>
            <div class="feature">
                <div class="feature-icon green">📊</div>
                <div class="feature-text">
                    <h4>Rapports financiers</h4>
                    <p>Chiffre d'affaires, dépenses, bénéfices, export PDF — tous vos indicateurs financiers en un coup d'œil.</p>
                </div>
            </div>
            <div class="feature">
                <div class="feature-icon orange">🏷️</div>
                <div class="feature-text">
                    <h4>Codes de réduction & Parrainage</h4>
                    <p>Créez des codes promo, gérez vos campagnes de fidélisation et suivez vos parrainages.</p>
                </div>
            </div>
        </div>

        {{-- Étapes de démarrage --}}
        <div class="section-title">Pour bien démarrer</div>
        <div class="steps">
            <div class="step">
                <div class="step-num">1</div>
                <p>Complétez <strong>le profil de votre établissement</strong> (nom, adresse, logo) depuis “Mon Établissement”.</p>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <p>Ajoutez vos <strong>prestations et produits</strong> pour qu'ils apparaissent à la caisse.</p>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <p>Commencez à <strong>enregistrer vos ventes</strong> depuis l'onglet Caisse.</p>
            </div>
            <div class="step">
                <div class="step-num">4</div>
                <p>Choisissez votre <strong>plan d'abonnement</strong> pour accéder à toutes les fonctionnalités Premium.</p>
            </div>
        </div>

        <div class="cta">
            <a href="{{ url('/dashboard') }}" class="btn">🚀 &nbsp;Accéder à mon espace</a>
        </div>

        <div class="support">
            💬 &nbsp;Une question ? Notre équipe est là pour vous aider.<br>
            Contactez-nous sur <a href="{{ url('/contact') }}">{{ url('/contact') }}</a>
        </div>

    </div>

    <div class="footer">
        Vous recevez cet email car vous venez de créer un compte sur <a href="{{ url('/') }}">{{ config('app.name') }}</a>.<br>
        &copy; {{ date('Y') }} Maëlya Gestion — Tous droits réservés.
    </div>

</div>
</body>
</html>
