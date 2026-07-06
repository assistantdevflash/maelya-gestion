@component('mail::message')
# Merci pour votre commande !

Bonjour **{{ $commande->client_prenom }} {{ $commande->client_nom }}**,

Nous avons bien reçu votre commande **{{ $commande->numero }}** passée chez **{{ $institut->nom }}**.

## Récapitulatif

@component('mail::table')
| Produit | Quantité | Prix unitaire | Sous-total |
|:--------|:--------:|:-------------:|:----------:|
@foreach($commande->items as $item)
| {{ $item->nom_snapshot }} | {{ $item->quantite }} | {{ number_format($item->prix_snapshot, 0, ',', ' ') }} FCFA | {{ number_format($item->sous_total, 0, ',', ' ') }} FCFA |
@endforeach
@endcomponent

**Sous-total produits :** {{ number_format($commande->sous_total, 0, ',', ' ') }} FCFA  
**Frais de livraison :** {{ number_format($commande->frais_livraison, 0, ',', ' ') }} FCFA  
**Total à payer :** {{ number_format($commande->total, 0, ',', ' ') }} FCFA

## Adresse de livraison
{{ $commande->client_adresse }}

## Mode de paiement
💵 **Cash à la livraison**

@component('mail::button', ['url' => route('shop.suivi', ['slug' => $institut->slug, 'numero' => $commande->numero])])
Suivre ma commande
@endcomponent

Vous recevrez un email dès que votre commande sera expédiée.

Merci de votre confiance,  
L'équipe de **{{ $institut->nom }}**

@endcomponent
