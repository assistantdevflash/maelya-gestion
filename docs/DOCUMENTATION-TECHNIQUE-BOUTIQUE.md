# 🛍️ Module Boutique en ligne - Documentation technique

## 📦 Architecture générale

Le module boutique en ligne est une extension du système Maëlya Gestion qui permet à chaque établissement d'avoir sa propre boutique en ligne accessible publiquement.

### Principes de conception
- **Multi-tenant** : Chaque institut a sa propre boutique
- **Isolation** : Chaque boutique affiche uniquement ses produits
- **Performance** : Cache de 1 heure pour les catalogues
- **Sécurité** : Validation stricte + throttling anti-spam
- **Production-safe** : Aucune modification des fonctionnalités existantes

---

## 🗄️ Base de données

### Migration 1 : `add_boutique_fields_to_instituts_table`
Ajoute les champs de configuration boutique :
- `boutique_active` (boolean) - Activation on/off
- `boutique_frais_livraison` (decimal) - Frais de livraison fixes
- `boutique_zones_livraison` (json) - Zones/villes couvertes
- `boutique_delai_livraison` (string) - Ex: "24h", "2-3 jours"
- `boutique_conditions` (text) - Conditions générales

### Migration 2 : `create_commandes_table`
Table des commandes :
- `id` (uuid) - PK
- `institut_id` (uuid) - FK vers instituts
- `client_id` (uuid) - FK vers clients
- `vente_id` (uuid nullable) - FK vers ventes (créée au paiement)
- `numero` (string unique) - Format: CMD-YYYYMMDD-0001
- `nom_client`, `prenom_client`, `telephone_client`, `email_client`, `adresse_livraison` - Snapshot client
- `sous_total`, `frais_livraison`, `total` (decimal)
- `statut` (enum) - nouvelle, acceptee, en_preparation, en_livraison, livree, annulee, refusee
- `acceptee_at`, `en_preparation_at`, `en_livraison_at`, `livree_at`, `annulee_at`, `refusee_at` (timestamps workflow)
- `payee` (boolean), `payee_at` (timestamp)
- `notes_admin`, `notes_client` (text)
- `created_at`, `updated_at`, `deleted_at` (soft deletes)

### Migration 3 : `create_commande_items_table`
Table des lignes de commande :
- `id` (uuid) - PK
- `commande_id` (uuid) - FK avec cascade delete
- `produit_id` (uuid) - FK vers produits
- `nom_snapshot` - Nom du produit au moment de la commande
- `prix_snapshot` - Prix au moment de la commande
- `quantite` (integer)
- `sous_total` (decimal) - Auto-calculé
- `created_at`, `updated_at`

---

## 📐 Modèles

### `Commande.php`
**Traits** : HasUuids, SoftDeletes, BelongsToInstitut

**Relations** :
- `belongsTo(Institut::class)`
- `belongsTo(Client::class)`
- `belongsTo(Vente::class)`
- `hasMany(CommandeItem::class)`

**Méthodes métier** :
- `genererNumero()` - Génère CMD-YYYYMMDD-XXXX
- `changerStatut($nouveau, $notes = null)` - Change le statut avec timestamps
- `peutEtreAnnulee()` - Vérifie si annulation possible
- `peutEtreMarqueePayee()` - Vérifie si peut marquer payée
- `calculerTotaux()` - Recalcule sous_total, frais, total

**Scopes** :
- `nouvelles()` - Statut = nouvelle
- `enCours()` - Statuts intermédiaires
- `livrees()` - Statut = livree
- `payees()` - payee = true
- `nonPayees()` - payee = false

**Casts** :
- Dates workflow (acceptee_at, en_preparation_at, etc.)
- Montants en decimal

### `CommandeItem.php`
**Traits** : HasUuids, BelongsToInstitut

**Relations** :
- `belongsTo(Commande::class)`
- `belongsTo(Produit::class)`

**Événements** :
- `saving` : Calcule automatiquement le sous_total

### `Institut.php` (étendu)
**Nouveaux champs fillable** :
- `boutique_active`, `boutique_frais_livraison`, `boutique_zones_livraison`, `boutique_delai_livraison`, `boutique_conditions`

**Nouveaux casts** :
- `boutique_zones_livraison` : array

**Nouvelles relations** :
- `hasMany(Commande::class)`

---

## 🎮 Contrôleurs

### `BoutiqueController.php` (Public)

#### `index($slug)`
- Affiche le catalogue de produits
- Cache de 1 heure
- Filtre : actif=true, stock>0
- Retourne : view boutique.index

#### `produit($slug, $id)`
- Détails d'un produit
- Produits similaires (même catégorie)
- Retourne : view boutique.produit

#### `commander(Request $request, $slug)`
- **Validation** : tous les champs requis
- **Throttle** : 5 commandes/heure/IP
- **Vérifications** :
  * Boutique active
  * Panier non vide
  * Produits existent et sont actifs
  * Stock suffisant
- **Transaction** :
  * Crée/trouve le client
  * Crée la commande
  * Crée les items
  * Invalide le cache
  * Envoie emails
  * Envoie notifications push
- Retourne : redirect vers suivi

#### `suivreCommande($slug, $numero)`
- Affiche la commande et sa timeline
- Accessible sans auth
- Retourne : view boutique.suivi

### `BoutiqueConfigController.php` (Dashboard Admin)

#### `index()`
- Affiche le formulaire de configuration
- URL de la boutique générée
- Retourne : view dashboard.boutique.config

#### `update(Request $request)`
- **Validation** : frais_livraison >= 0
- **Autorisation** : Admin uniquement (middleware)
- Sauvegarde les paramètres
- Invalide le cache
- Retourne : redirect avec message succès

### `CommandeController.php` (Dashboard)

#### `index(Request $request)`
- Liste toutes les commandes de l'institut
- **Filtres** :
  * Statut
  * Payée/non payée
  * Recherche (numero, nom, prénom, téléphone)
- **Stats** :
  * Nouvelles
  * En cours
  * Livrées
  * CA total
- **Pagination** : 20/page
- **Autorisation** : Admin + Employés
- Retourne : view dashboard.boutique.commandes.index

#### `show(Commande $commande)`
- Détails complets d'une commande
- **Autorisation** : Policy (même institut)
- Retourne : view dashboard.boutique.commandes.show

#### `updateStatut(Request $request, Commande $commande)`
- Change le statut d'une commande
- **Validation** : statut valide
- **Autorisation** : Admin uniquement (Policy)
- **Actions** :
  * Change le statut (via modèle)
  * Envoie email au client
  * Envoie notification push
- Retourne : redirect avec message succès

#### `marquerPayee(Commande $commande)`
- Marque la commande comme payée
- **Autorisation** : Admin uniquement (Policy)
- **Vérifications** :
  * Statut = livree
  * Pas déjà payée
- **Transaction** :
  * Crée une Vente
  * Crée les VenteItems
  * Déduit le stock
  * Marque commande payée
  * Lie vente et commande
- Retourne : redirect avec message succès

#### `updateNotes(Request $request, Commande $commande)`
- Met à jour les notes admin
- **Autorisation** : Admin uniquement (Policy)
- Retourne : redirect avec message succès

#### `destroy(Commande $commande)`
- Supprime une commande (soft delete)
- **Autorisation** : Admin uniquement (Policy)
- **Vérifications** : Statut = annulee OU refusee
- Retourne : redirect avec message succès

---

## 🔐 Sécurité

### `CommandePolicy.php`

- `viewAny()` : Admin + Employé
- `view()` : Même institut
- `update()` : Admin uniquement
- `delete()` : Admin uniquement

### Middleware appliqués

**Routes publiques** (`/shop/{slug}/*`) :
- Aucun middleware auth
- Throttle sur `/commander` : 5 req/heure/IP

**Routes dashboard** (`/dashboard/boutique/*`) :
- `auth` : Authentification requise
- `institut` : Institut en session requis
- Middleware implicites via groupe dashboard

### Validation des données

**Commander** :
- `prenom` : requis, string, max:255
- `nom` : requis, string, max:255
- `telephone` : requis, string, max:20
- `email` : nullable, email
- `adresse` : requis, string
- `panier` : requis, array, min:1
- `panier.*.id` : requis, exists:produits
- `panier.*.quantite` : requis, integer, min:1

**Update Config** :
- `boutique_active` : boolean
- `boutique_frais_livraison` : nullable, numeric, min:0
- `boutique_zones_livraison` : nullable, string
- `boutique_delai_livraison` : nullable, string, max:100
- `boutique_conditions` : nullable, string

**Update Statut** :
- `statut` : requis, in:[acceptee,en_preparation,en_livraison,livree,annulee,refusee]

**Update Notes** :
- `notes_admin` : nullable, string

---

## 📧 Emails

### `NouvelleCommandeClient.php`
- **Envoyé à** : Client (si email fourni)
- **Quand** : Après création de la commande
- **Template** : `emails.commande.nouvelle-client`
- **Variables** : $commande, $institut
- **Contenu** :
  * Confirmation de commande
  * Récapitulatif produits
  * Totaux
  * Lien de suivi

### `NouvelleCommandeEtablissement.php`
- **Envoyé à** : Admin de l'institut
- **Quand** : Après création de la commande
- **Template** : `emails.commande.nouvelle-etablissement`
- **Variables** : $commande, $institut
- **Contenu** :
  * Alerte nouvelle commande
  * Info client
  * Récapitulatif produits
  * Lien dashboard

### `CommandeStatutUpdatedClient.php`
- **Envoyé à** : Client (si email fourni)
- **Quand** : À chaque changement de statut
- **Template** : `emails.commande.statut-updated-client`
- **Variables** : $commande, $institut
- **Sujet dynamique** : Selon le statut
- **Contenu** :
  * Message personnalisé par statut
  * Icône de statut
  * Lien de suivi

---

## 🔔 Notifications

### Notification base de données
**Envoyée à** : Admin de l'institut  
**Service** : `NotificationService`  
**Titre** : "Nouvelle commande"  
**Message** : "Commande {numero} de {montant} FCFA"  
**Type** : "commande_nouvelle"  
**Lien** : `/dashboard/boutique/commandes/{id}`

### Notification push
**Envoyée à** : Admin de l'institut  
**Service** : `PushNotificationService`  
**Titre** : "🛍️ Nouvelle commande"  
**Body** : "Commande {numero} de {montant} FCFA"  
**URL** : `/dashboard/boutique/commandes/{id}`

---

## 🎨 Vues

### Layout : `boutique/layouts/app.blade.php`
- HTML5 sémantique
- Meta Open Graph (Facebook, WhatsApp, Instagram)
- Twitter Card
- Responsive viewport
- Alpine.js 3.x
- Tailwind CSS
- Favicon dynamique

### `boutique/index.blade.php`
**Composant Alpine** : `panier()`
- **État** :
  * `items` : Tableau produits
  * `montantPanier` : Total calculé
  * `modalOuverte` : Boolean
  * `modalCommandeOuverte` : Boolean
  * `fraisLivraison` : From institut
  * `formulaire` : Objet champs client
  
- **Méthodes** :
  * `init()` : Charge depuis localStorage
  * `ajouterAuPanier(produit)`
  * `augmenterQuantite(index)`
  * `diminuerQuantite(index)`
  * `retirerDuPanier(index)`
  * `viderPanier()`
  * `sauvegarder()` : Dans localStorage
  * `ouvrirCommande()`
  * `commander()` : Submit form

- **UI** :
  * Header avec logo/nom
  * Grille produits responsive (1-4 cols)
  * Modal panier (slide-in bottom mobile)
  * Modal commande avec formulaire
  * Bouton panier flottant avec badge quantité

### `boutique/suivi.blade.php`
- Badge statut coloré
- Timeline verticale avec workflow
- Timestamps des étapes
- Récapitulatif produits
- Totaux détaillés
- Adresse de livraison
- Bouton "Continuer mes achats"

### `dashboard/boutique/config.blade.php`
- Toggle boutique_active
- Affichage URL boutique + copie
- Input frais livraison
- Input délai livraison
- Textarea zones
- Textarea conditions
- Bouton enregistrer

### `dashboard/boutique/commandes/index.blade.php`
- 4 cards statistiques
- Formulaire filtres (statut, paiement, recherche)
- Table responsive avec :
  * Numero (lien vers show)
  * Client
  * Date
  * Total
  * Statut (badge)
  * Payée (badge)
- Pagination Laravel

### `dashboard/boutique/commandes/show.blade.php`
- En-tête avec numéro + badges
- Card info client
- Card produits commandés
- Card totaux
- Form changement statut (admin only)
- Bouton marquer payée (admin only, si livree)
- Form notes admin (admin only)
- Bouton supprimer (admin only, si annulee/refusee)

### Templates email (Markdown)
- `emails/commande/nouvelle-client.blade.php`
- `emails/commande/nouvelle-etablissement.blade.php`
- `emails/commande/statut-updated-client.blade.php`

Utilisent les composants Laravel Mail :
- `@component('mail::message')`
- `@component('mail::button')`
- `@component('mail::table')`

---

## 🔄 Workflows

### Workflow de commande

```
Nouvelle
  ↓
Acceptée (par admin)
  ↓
En préparation (par admin)
  ↓
En livraison (par admin)
  ↓
Livrée (par admin)
  ↓
Marquer payée (par admin) → Crée Vente + déduit stock

Chemins alternatifs :
Nouvelle → Refusée (par admin)
Acceptée/En préparation → Annulée (par admin)
```

### Workflow de paiement

1. Commande créée → `payee = false`
2. Commande livrée → Bouton "Marquer payée" apparaît
3. Admin clique "Marquer payée" :
   - Crée une Vente
   - Lie vente_id dans commande
   - Met `payee = true`, `payee_at = now()`
   - Déduit stock via VenteItems
4. Vente visible dans module Ventes normal

---

## 🚀 Performance

### Cache
- **Clé** : `boutique_produits_{institut_id}`
- **TTL** : 1 heure (3600s)
- **Invalidation** :
  * Après création commande
  * Après update config boutique

### Optimisations
- Eager loading : `with(['items.produit'])`
- Pagination : 20 items par défaut
- Index DB : `numero` unique
- Soft deletes : Archive au lieu de supprimer

### Requêtes optimisées
```php
// Liste commandes avec stats
Commande::where('institut_id', $institut_id)
    ->with('client')
    ->withCount('items')
    ->latest()
    ->paginate(20);

// Stats en une requête
Commande::where('institut_id', $institut_id)
    ->selectRaw('
        COUNT(CASE WHEN statut = "nouvelle" THEN 1 END) as nouvelles,
        COUNT(CASE WHEN statut IN ("acceptee","en_preparation","en_livraison") THEN 1 END) as en_cours,
        COUNT(CASE WHEN statut = "livree" THEN 1 END) as livrees,
        SUM(CASE WHEN payee = 1 THEN total ELSE 0 END) as total_ca
    ')
    ->first();
```

---

## 🧪 Tests à effectuer

### Tests unitaires recommandés
- [ ] `Commande::genererNumero()` génère format correct
- [ ] `Commande::changerStatut()` met à jour le bon timestamp
- [ ] `Commande::peutEtreAnnulee()` retourne bon boolean
- [ ] `CommandeItem` calcule sous_total automatiquement
- [ ] `CommandePolicy` autorise correctement

### Tests d'intégration recommandés
- [ ] Commander avec stock insuffisant → erreur
- [ ] Commander avec boutique inactive → erreur
- [ ] Marquer payée crée bien une vente
- [ ] Stock déduit après paiement
- [ ] Emails envoyés correctement
- [ ] Notifications push reçues
- [ ] Cache invalidé après commande

### Tests manuels (voir GUIDE-TEST-BOUTIQUE.md)

---

## 📝 Notes de maintenance

### Ajout d'un nouveau mode de paiement
Modifier :
- `BoutiqueController::commander()` - Ajouter validation
- `boutique/index.blade.php` - Ajouter option radio
- `CommandeController::marquerPayee()` - Gérer nouveau mode dans Vente

### Ajout d'un nouveau statut
Modifier :
- Migration `create_commandes_table` - Ajouter à ENUM
- `Commande` model - Ajouter timestamp
- `Commande::changerStatut()` - Gérer nouveau timestamp
- `CommandeStatutUpdatedClient` - Ajouter message

### Modification du calcul des frais
Actuellement : Frais fixes par institut  
Pour rendre dynamique : Créer table `zones_livraison` avec tarifs

### Support multi-devises
Actuellement : FCFA hardcodé  
Pour multi-devises : Ajouter champ `devise` dans instituts

---

## 🐛 Debugging

### Logs à vérifier
- `storage/logs/laravel.log` - Erreurs PHP
- Console navigateur - Erreurs JS
- Network tab - Requêtes AJAX

### Commandes utiles
```bash
# Voir les migrations
php artisan migrate:status

# Vider le cache
php artisan cache:clear
php artisan view:clear
php artisan config:clear

# Voir les routes
php artisan route:list --path=shop
php artisan route:list --path=boutique

# Recompiler assets
npm run build

# Mode maintenance
php artisan down
php artisan up
```

### Erreurs fréquentes

**"Boutique non disponible"**
→ Vérifier `boutique_active = true` dans DB

**"SQLSTATE: ENUM not found"**
→ SQLite : ENUM = TEXT, normal en dev

**"Class NotificationService not found"**
→ Vérifier namespace et autoload

**"Trying to get property of non-object"**
→ Vérifier eager loading des relations

---

## 📚 Ressources

- [Laravel Documentation](https://laravel.com/docs)
- [Alpine.js Documentation](https://alpinejs.dev)
- [Tailwind CSS Documentation](https://tailwindcss.com)
- [Open Graph Protocol](https://ogp.me)

---

**Version** : 1.0.0  
**Date** : Juillet 2026  
**Auteur** : GitHub Copilot  
**Licence** : Propriétaire Maëlya
