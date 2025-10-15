#!/bin/bash

# ======================================================================
# 🚀 LARAVEL DASHBOARD DEPLOYMENT SCRIPT
# ======================================================================
# This script pulls the latest code and clears all caches
# Usage: ./deploy_code.sh
# ======================================================================

set -e  # Exit on any error

echo "======================================================================"
echo "🚀 LARAVEL DASHBOARD DEPLOYMENT"
echo "======================================================================"
echo "This script will:"
echo "1. Pull latest code from git"
echo "2. Install/update Composer dependencies"
echo "3. Clear all Laravel caches"
echo "4. Optimize for production"
echo "======================================================================"

# Check if we're in a git repository
if [ ! -d ".git" ]; then
    echo "❌ Error: Not in a git repository. Please run this from the project root."
    exit 1
fi

# Check if we're in the right directory (should have composer.json)
if [ ! -f "composer.json" ]; then
    echo "❌ Error: composer.json not found. Please run this from the Laravel project root."
    exit 1
fi

echo "📥 STEP 1: PULLING LATEST CODE"
echo "======================================================================"
git fetch origin
git pull origin main
echo "✅ Code updated successfully!"

echo ""
echo "📦 STEP 2: COMPOSER DEPENDENCIES"
echo "======================================================================"
composer install --no-dev --optimize-autoloader
echo "✅ Dependencies updated!"

echo ""
echo "🧹 STEP 3: CLEARING LARAVEL CACHES"
echo "======================================================================"
php artisan cache:clear
echo "✅ Application cache cleared!"

php artisan config:clear
echo "✅ Configuration cache cleared!"

php artisan route:clear
echo "✅ Route cache cleared!"

php artisan view:clear
echo "✅ View cache cleared!"

echo ""
echo "⚡ STEP 4: OPTIMIZING FOR PRODUCTION"
echo "======================================================================"
php artisan config:cache
echo "✅ Configuration cached!"

php artisan route:cache
echo "✅ Routes cached!"

php artisan view:cache
echo "✅ Views cached!"

php artisan optimize
echo "✅ Application optimized!"

echo ""
echo "🔍 STEP 5: VERIFICATION"
echo "======================================================================"
echo "Checking Laravel application status..."
php artisan about --only=environment,laravel,php,server
echo ""

echo "======================================================================"
echo "✅ DEPLOYMENT COMPLETE!"
echo "======================================================================"
echo "🎯 Your Laravel dashboard has been updated and optimized!"
echo "🌐 Access it at: https://rkmnd.fitform100.net"
echo "======================================================================"
