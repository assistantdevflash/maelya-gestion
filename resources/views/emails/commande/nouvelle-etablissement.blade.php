@component('mail::message')
# 🛒 Nouvelle commande boutique

Une nouvelle commande vient d'être passée sur votre boutique en ligne !

## Informations client

**Nom :** {{ $commande->client_prenom }} {{ $commande->client_nom }}  
**Téléphone :** {{ $commande->client_telephone }}  
@if($commande->client_email)
**Email :** {{ $commande->client_email }}  
@endif
**Adresse :** {{ $commande->client_adresse }}

@if($commande->notes_client)
**Notes du client :** {{ $commande->notes_client }}
@endif

## Produits commandés

@component('mail::table')
| Produit | Quantité | Prix unitaire | Sous-total |
|:--------|:--------:|:-------------:|:----------:|
@foreach($commande->items as $item)
| {{ $item->nom_snapshot }} | {{ $item->quantite }} | {{ number_format($item->prix_snapshot, 0, ',', ' ') }} FCFA | {{ number_format($item->sous_total, 0, ',', ' ') }} FCFA |
@endforeach
@endcomponent

**Total à encaisser :** {{ number_format($commande->total, 0, ',', ' ') }} FCFA (cash à la livraison)

@component('mail::button', ['url' => route('dashboard.boutique.commandes.show', $commande)])
Gérer cette commande
@endcomponent

Merci,  
Le système Maëlya Gestion

@endcomponent
