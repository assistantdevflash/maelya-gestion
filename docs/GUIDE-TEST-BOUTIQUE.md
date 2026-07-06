# 🛍️ Guide de test - Module Boutique en ligne

## ✅ Étape 1 : Configuration de la boutique

1. **Se connecter au dashboard** en tant qu'admin
2. Aller dans **Dashboard > Boutique > Configuration** (nouvelle section dans le menu)
3. **Activer la boutique** en cochant la case
4. Configurer :
   - Frais de livraison (ex: 1000 FCFA)
   - Délai de livraison (ex: "24h")
   - Zones de livraison (optionnel)
5. **Copier le lien de votre boutique** affiché
6. Cliquer sur **Enregistrer**

## ✅ Étape 2 : Ajouter des produits

1. Aller dans **Produits**
2. S'assurer qu'au moins quelques produits sont :
   - ✅ Actifs
   - ✅ Ont du stock (> 0)
   - ✅ Ont un prix de vente
   - ✅ Ont une photo (optionnel mais recommandé)

## ✅ Étape 3 : Tester la boutique publique

1. **Ouvrir le lien de la boutique** (copié à l'étape 1) dans un nouvel onglet
2. Vérifier que :
   - Le logo et nom de l'établissement s'affichent
   - Les produits sont visibles
   - Les prix sont corrects

3. **Ajouter des produits au panier** :
   - Cliquer sur "Ajouter" sur un produit
   - Le panier s'ouvre automatiquement
   - Modifier les quantités avec + / -
   - Ajouter d'autres produits

4. **Passer une commande** :
   - Cliquer sur "Commander"
   - Remplir le formulaire :
     * Prénom et Nom
     * Téléphone (obligatoire)
     * Email (optionnel mais recommandé pour recevoir les emails)
     * Adresse de livraison
   - Cliquer sur "Valider ma commande"

5. **Page de confirmation** :
   - Vérifier que le numéro de commande est affiché (ex: CMD-20260707-0001)
   - Vérifier la timeline du statut
   - Vérifier les totaux

## ✅ Étape 4 : Gérer la commande (Dashboard)

1. Retourner au **Dashboard**
2. Aller dans **Boutique > Commandes**
3. Vérifier que :
   - La nouvelle commande apparaît avec statut "Nouvelle"
   - Les stats sont mises à jour

4. **Cliquer sur la commande** pour voir les détails
5. **Changer le statut** :
   - Nouvelle → Acceptée
   - Acceptée → En préparation
   - En préparation → En livraison
   - En livraison → Livrée

6. Quand le statut est "Livrée", **cliquer sur "Marquer comme payée"**
   - ✅ Une vente est automatiquement créée
   - ✅ Le stock est déduit
   - ✅ Le CA est mis à jour

7. Vérifier dans **Ventes** que la vente a bien été créée

## 🧪 Tests supplémentaires

### Test des notifications
- ✅ Vérifier que vous recevez une notification push quand une commande arrive
- ✅ Vérifier que le client reçoit un email de confirmation (si email fourni)

### Test du stock
- ✅ Essayer de commander plus que le stock disponible
- ✅ Vérifier que le message d'erreur s'affiche

### Test du panier
- ✅ Vider le panier
- ✅ Fermer et rouvrir le panier (les données sont en localStorage)
- ✅ Tester sur mobile (responsive)

### Test des routes
- ✅ `/shop/votre-slug` - Page boutique
- ✅ `/shop/votre-slug/produit/{id}` - Détails produit (à créer si besoin)
- ✅ `/shop/votre-slug/commande/{numero}` - Suivi commande

## 🐛 Problèmes potentiels et solutions

### "Boutique non disponible"
- Vérifier que `boutique_active = true` dans la config

### "Aucun produit disponible"
- Vérifier que les produits ont :
  * `actif = true`
  * `stock > 0`

### "Erreur lors de la commande"
- Vérifier les logs : `storage/logs/laravel.log`
- Vérifier que les produits existent toujours
- Vérifier que le stock n'a pas changé entre-temps

### Emails non reçus
- Vérifier la config mail dans `.env`
- Vérifier les logs : `storage/logs/laravel.log`

### Notifications push non reçues
- Vérifier que le service worker est actif
- Vérifier que les notifications sont autorisées dans le navigateur

## 📱 Test sur mobile

1. Ouvrir la boutique sur un smartphone
2. Vérifier que :
   - Le design est responsive
   - Le panier est facilement accessible
   - Le formulaire de commande est utilisable
   - Les boutons sont assez grands (44x44px minimum)

## ✅ Checklist finale

- [ ] Configuration boutique activée
- [ ] Au moins 3 produits avec stock et prix
- [ ] Commande passée avec succès
- [ ] Email de confirmation reçu
- [ ] Notification push reçue (admin)
- [ ] Statut modifié dans dashboard
- [ ] Commande marquée comme payée
- [ ] Vente créée automatiquement
- [ ] Stock déduit correctement
- [ ] CA mis à jour
- [ ] Test sur mobile réussi

---

## 🚀 Déploiement en production

Une fois les tests locaux validés :

```bash
git push origin main

# Sur le serveur LWS :
cd ~/maelya && git pull origin main
php artisan migrate --force
php artisan view:clear
php artisan config:clear
php artisan cache:clear
```

**⚠️ Important** : Toujours faire un backup de la base de données avant de déployer !

---

## 📞 Support

En cas de problème :
1. Vérifier les logs : `storage/logs/laravel.log`
2. Vérifier la console du navigateur (F12)
3. Vérifier que toutes les migrations sont passées : `php artisan migrate:status`
