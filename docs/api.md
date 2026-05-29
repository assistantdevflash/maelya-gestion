# API REST Maëlya Gestion

Authentification : **Laravel Sanctum** (Personal Access Tokens).
Base URL : `https://<votre-domaine>/api`

Toutes les réponses sont au format JSON. Les routes protégées exigent l'en-tête :

```
Authorization: Bearer <token>
Accept: application/json
```

---

## 🔐 Authentification

### POST `/api/login`
Échange email/mot de passe contre un token.

**Body**
```json
{ "email": "admin@institut.com", "password": "******", "device_name": "mobile-android" }
```

**Réponse 200**
```json
{
  "token": "1|aBcD1234...",
  "user": { "id": "uuid", "name": "Aïcha", "email": "...", "role": "admin", "institut_id": "uuid" }
}
```

**Exemple cURL**
```bash
curl -X POST https://maelya.app/api/login \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"email":"admin@x.com","password":"secret"}'
```

### POST `/api/logout`
Révoque le token courant. Requiert auth.

### GET `/api/me`
Renvoie les infos de l'utilisateur authentifié.

---

## 👥 Clients

### GET `/api/clients`
Liste paginée des clients de l'institut courant.

**Query params** : `q` (recherche nom/prénom/tel), `per_page` (défaut 25, max 100)

```bash
curl https://maelya.app/api/clients?q=marie \
  -H "Authorization: Bearer $TOKEN" -H "Accept: application/json"
```

### GET `/api/clients/{id}`
Détail d'un client (avec historique de points).

---

## 📦 Produits

### GET `/api/produits`
Liste des produits actifs.

**Query params** : `alertes_seulement` (bool — stock ≤ seuil_alerte), `per_page`

### GET `/api/produits/{id}`

---

## 💰 Ventes

### GET `/api/ventes`
Liste paginée des ventes.

**Query params** : `date_debut`, `date_fin` (YYYY-MM-DD), `statut`, `per_page`

### GET `/api/ventes/{id}`
Détail avec `items`, `paiements`, `client`.

---

## ⚠️ Erreurs

| Code | Sens                                            |
|------|-------------------------------------------------|
| 401  | Token manquant / invalide                       |
| 403  | Accès refusé (ressource d'un autre institut)    |
| 404  | Ressource introuvable                           |
| 422  | Validation échouée — voir `errors` dans la réponse |
| 429  | Trop de requêtes (rate limit, ex. /login 6/min) |
