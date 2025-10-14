#!/bin/bash

# TuneUp Fitness AI Portal - Deployment Script
# Target: rkmnd.fitform100.net
# Date: October 14, 2025

echo "🚀 TuneUp Fitness AI Portal - Production Deployment"
echo "=================================================="
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Configuration
APP_DIR="/var/www/html/rkmnd.fitform100.net"
DOMAIN="rkmnd.fitform100.net"

echo "📂 Target Directory: $APP_DIR"
echo "🌐 Domain: $DOMAIN"
echo ""

# Check if running on server
if [ ! -d "$APP_DIR" ]; then
    echo -e "${RED}❌ Error: Directory $APP_DIR does not exist${NC}"
    echo "Please run this script on the production server."
    exit 1
fi

# Navigate to app directory
cd $APP_DIR

echo -e "${YELLOW}⏳ Step 1: Pulling latest code from GitHub...${NC}"
git pull origin main
echo -e "${GREEN}✅ Code updated${NC}"
echo ""

echo -e "${YELLOW}⏳ Step 2: Installing/updating dependencies...${NC}"
composer install --no-dev --optimize-autoloader
echo -e "${GREEN}✅ Dependencies installed${NC}"
echo ""

echo -e "${YELLOW}⏳ Step 3: Running database migrations...${NC}"
php artisan migrate --force
echo -e "${GREEN}✅ Database migrated${NC}"
echo ""

echo -e "${YELLOW}⏳ Step 4: Clearing caches...${NC}"
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
echo -e "${GREEN}✅ Caches cleared${NC}"
echo ""

echo -e "${YELLOW}⏳ Step 5: Optimizing for production...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo -e "${GREEN}✅ Application optimized${NC}"
echo ""

echo -e "${YELLOW}⏳ Step 6: Setting permissions...${NC}"
chmod -R 755 $APP_DIR
chmod -R 775 $APP_DIR/storage
chmod -R 775 $APP_DIR/bootstrap/cache
echo -e "${GREEN}✅ Permissions set${NC}"
echo ""

echo -e "${GREEN}🎉 Deployment completed successfully!${NC}"
echo ""
echo "🌐 Your app should now be live at: https://$DOMAIN"
echo ""
echo "📋 Next steps:"
echo "  1. Visit https://$DOMAIN to verify"
echo "  2. Test login functionality"
echo "  3. Check analytics dashboard"
echo "  4. Monitor logs: tail -f storage/logs/laravel.log"
echo ""

