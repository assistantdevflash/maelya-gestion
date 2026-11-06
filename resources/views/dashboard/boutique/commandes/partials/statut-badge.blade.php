@php
    // Couleurs et libellés des statuts de commande, partagés par la liste,
    // la fiche et les cartes mobiles.
    $palette = [
        'nouvelle'       => 'bg-primary-100 text-primary-800 dark:bg-primary-900/40 dark:text-primary-300',
        'acceptee'       => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
        'en_preparation' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
        'en_livraison'   => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/40 dark:text-cyan-300',
        'livree'         => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
        'annulee'        => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
        'refusee'        => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
    ];
    $libelle = [
        'nouvelle'       => 'Nouvelle',
        'acceptee'       => 'Acceptée',
        'en_preparation' => 'En préparation',
        'en_livraison'   => 'En livraison',
        'livree'         => 'Livrée',
        'annulee'        => 'Annulée',
        'refusee'        => 'Refusée',
    ];
    $classe = $palette[$statut] ?? 'bg-gray-100 text-gray-700 dark:bg-slate-700 dark:text-slate-300';
@endphp
<span class="inline-block px-3 py-1.5 text-xs font-semibold rounded-lg {{ $classe }}">{{ $libelle[$statut] ?? ucfirst($statut) }}</span>
