#!/bin/bash
# =============================================================================
# Script de déploiement Maëlya Gestion — cPanel LWS
# Usage : bash deploy.sh
# À exécuter via SSH sur le serveur cPanel
# =============================================================================

set -e

PHP="/usr/local/php82/bin/php"
COMPOSER="/usr/local/php82/bin/php /usr/local/bin/composer"

echo "▶ Déploiement Maëlya Gestion..."

# 1. Mettre en maintenance
$PHP artisan down --render="errors.503" || true

# 2. Pull des dernières modifications (si Git configuré)
# git pull origin main

# 3. Installer les dépendances PHP (sans dev)
$COMPOSER install --no-dev --optimize-autoloader --no-interaction

# 4. Copier le .env de production si nécessaire
# cp .env.production .env

# 5. Générer la clé si première installation
# $PHP artisan key:generate

# 6. Migrations
$PHP artisan migrate --force

# 7. Vider et reconstruire les caches
$PHP artisan config:clear
$PHP artisan config:cache
$PHP artisan route:clear
$PHP artisan route:cache
$PHP artisan view:clear
$PHP artisan view:cache
$PHP artisan event:cache

# 8. Storage link
$PHP artisan storage:link || true

# 9. Permissions
chmod -R 775 storage bootstrap/cache

# 10. Remettre en ligne
$PHP artisan up

echo "✅ Déploiement terminé avec succès !"
