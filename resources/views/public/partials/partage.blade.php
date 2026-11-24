{{--
    Balises de partage des pages personnelles : carte de fidélité, suivi de
    crédit, sondage d'avis.

    Deux règles :
      • jamais d'indexation — ce sont des liens privés à jeton ;
      • aucun aperçu révélateur — ni nom du client, ni solde, ni montant.
        Seul le nom de l'établissement apparaît, accompagné de son logo.

    Paramètres attendus : $institut (nullable), $titrePartage, $descriptionPartage.
--}}
@php
    $ogInstitut = $institut ?? null;
    $ogImage = \App\Helpers\SeoHelper::imagePourInstitut($ogInstitut);
    $ogSite = $ogInstitut?->nom ?? 'Maëlya Gestion';
@endphp
<meta name="robots" content="noindex, nofollow">
<meta name="description" content="{{ $descriptionPartage }}">
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ $ogSite }}">
<meta property="og:locale" content="fr_CI">
<meta property="og:title" content="{{ $titrePartage }}">
<meta property="og:description" content="{{ $descriptionPartage }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="{{ $ogImage }}">
@if(\App\Helpers\SeoHelper::estImageDefaut($ogImage))
<meta property="og:image:type" content="image/png">
<meta property="og:image:width" content="{{ \App\Helpers\SeoHelper::LARGEUR_DEFAUT }}">
<meta property="og:image:height" content="{{ \App\Helpers\SeoHelper::HAUTEUR_DEFAUT }}">
@endif
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $titrePartage }}">
<meta name="twitter:description" content="{{ $descriptionPartage }}">
<meta name="twitter:image" content="{{ $ogImage }}">
<meta name="twitter:image:alt" content="{{ $ogSite }}">
