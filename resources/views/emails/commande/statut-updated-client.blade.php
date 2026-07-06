@component('mail::message')
# Mise à jour de votre commande

Bonjour **{{ $commande->client_prenom }} {{ $commande->client_nom }}**,

Votre commande **{{ $commande->numero }}** a été mise à jour.

## Nouveau statut

@php
    $statutLabels = [
        'nouvelle' => '📦 Nouvelle commande',
        'acceptee' => '✅ Acceptée',
        'en_preparation' => '👨‍🍳 En préparation',
        'en_livraison' => '🚚 En cours de livraison',
        'livree' => '✅ Livrée',
        'annulee' => '❌ Annulée',
        'refusee' => '❌ Refusée',
    ];
    $statutLabel = $statutLabels[$commande->statut] ?? $commande->statut;
@endphp

**{{ $statutLabel }}**

@if($commande->statut === 'en_livraison')
Votre commande est en route ! Vous devriez la recevoir très bientôt.
@elseif($commande->statut === 'livree')
Votre commande a été livrée. Merci de confirmer la réception.
@elseif($commande->statut === 'annulee' || $commande->statut === 'refusee')
Nous sommes désolés, votre commande a été {{ $commande->statut === 'annulee' ? 'annulée' : 'refusée' }}.
@endif

@component('mail::button', ['url' => route('shop.suivi', ['slug' => $commande->institut->slug, 'numero' => $commande->numero])])
Voir ma commande
@endcomponent

Merci,  
L'équipe de **{{ $commande->institut->nom }}**

@endcomponent
