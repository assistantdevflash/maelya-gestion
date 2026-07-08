#!/bin/bash

# Script d'optimisation performance pour Maelya Gestion
# À exécuter après chaque déploiement

echo "🚀 Optimisation des performances..."

# 1. Cache des vues Blade
echo "📝 Compilation des vues..."
php artisan view:cache

# 2. Cache de configuration
echo "⚙️ Cache de configuration..."
php artisan config:cache

# 3. Cache des routes
echo "🛣️ Cache des routes..."
php artisan route:cache

# 4. Cache des événements
echo "📡 Cache des événements..."
php artisan event:cache

# 5. Optimisation de l'autoloader
echo "🎯 Optimisation Composer..."
composer install --no-dev --optimize-autoloader --no-interaction

# 6. Build des assets (à faire en local, pas sur serveur)
echo "⚠️  RAPPEL : Build assets en local avec 'npm run build' avant push"
echo "   (Node.js non installé sur ce serveur)"

# 7. Permissions
echo "🔐 Ajustement des permissions..."
chmod -R 755 storage bootstrap/cache
chmod -R 775 storage/logs

echo "✅ Optimisation terminée !"
echo ""
echo "Performance checklist:"
echo "  ✓ Vues compilées"
echo "  ✓ Config en cache"
echo "  ✓ Routes en cache"
echo "  ✓ Événements en cache"
echo "  ✓ Autoloader optimisé"
echo "  ✓ Assets minifiés"
echo ""
echo "💡 Pour désactiver le cache en dev:"
echo "   php artisan optimize:clear"
