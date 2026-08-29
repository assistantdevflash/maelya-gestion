{{--
  Partial : variables CSS couleurs de l'établissement pour la boutique/vitrine.
  Usage : @include('boutique.partials.couleurs', ['institut' => $institut])
  Injecte : --couleur-primaire / --couleur-secondaire / --couleur-accent
  + classes utilitaires .boutique-* (btn, badge, accent, border, text, soft, ring)
--}}
@php
    $cp = $institut->couleur_primaire ?? '#7c3aed';
    $cs = $institut->couleur_secondaire ?? '#ec4899';
    $ca = $institut->couleur_accent ?? '#f59e0b';
@endphp
<style>
    :root {
        --couleur-primaire: {{ $cp }};
        --couleur-secondaire: {{ $cs }};
        --couleur-accent: {{ $ca }};
    }
    .boutique-header { background: linear-gradient(135deg, var(--couleur-primaire), var(--couleur-secondaire)); }
    .boutique-btn { background-color: var(--couleur-primaire); color: #fff; }
    .boutique-btn:hover { opacity: .9; }
    .boutique-badge { background-color: var(--couleur-secondaire); color: #fff; }
    .boutique-accent { color: var(--couleur-accent); }
    .boutique-accent-hover:hover { color: var(--couleur-accent); }
    .boutique-text { color: var(--couleur-primaire); }
    .boutique-border { border-color: var(--couleur-primaire); }
    .boutique-bg { background-color: var(--couleur-primaire); }
    .boutique-soft { background-color: {{ $cp }}14; }
    .boutique-ring:focus { --tw-ring-color: var(--couleur-primaire); }
    .boutique-ring-active { --tw-ring-color: var(--couleur-primaire); box-shadow: 0 0 0 2px var(--couleur-primaire); }
</style>
