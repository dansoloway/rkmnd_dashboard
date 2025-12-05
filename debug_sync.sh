#!/bin/bash

# Debug script for sync errors
# Run this on the Laravel Dashboard server

echo "╔═══════════════════════════════════════════════════════════════════╗"
echo "║           DEBUGGING SYNC ERRORS - LARAVEL DASHBOARD              ║"
echo "╚═══════════════════════════════════════════════════════════════════╝"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo -e "${RED}❌ Error: Not in Laravel project directory${NC}"
    echo "Please run this script from: /home/bitnami/htdocs/rkmnd.fitform100.net"
    echo ""
    echo "Laravel Dashboard Server Info:"
    echo "  Server IP: 35.155.88.31"
    echo "  User: bitnami"
    echo "  Path: /home/bitnami/htdocs/rkmnd.fitform100.net"
    exit 1
fi

echo "📁 Current directory: $(pwd)"
echo ""

# Step 1: Check Laravel logs
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "STEP 1: Checking Laravel Logs"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if [ -f "storage/logs/laravel.log" ]; then
    echo -e "${YELLOW}Recent sync-related errors:${NC}"
    tail -n 100 storage/logs/laravel.log | grep -i "sync\|error\|exception" | tail -20
    echo ""
else
    echo -e "${RED}❌ Laravel log file not found${NC}"
fi

# Step 2: Check API configuration
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "STEP 2: Checking API Configuration"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if [ -f ".env" ]; then
    echo -e "${YELLOW}Backend API URL:${NC}"
    grep "BACKEND_API_URL" .env || echo "Not found in .env"
    echo ""
else
    echo -e "${RED}❌ .env file not found${NC}"
fi

# Step 3: Test API connectivity
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "STEP 3: Testing API Connectivity"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
API_URL=$(grep "BACKEND_API_URL" .env 2>/dev/null | cut -d '=' -f2 | tr -d '"' | tr -d "'" | xargs)
if [ -z "$API_URL" ]; then
    API_URL="http://52.41.213.228:8000"
fi

echo -e "${YELLOW}Testing connection to: $API_URL${NC}"
if curl -s --max-time 5 -o /dev/null -w "%{http_code}" "$API_URL/api/v1/health" > /tmp/http_code.txt; then
    HTTP_CODE=$(cat /tmp/http_code.txt)
    if [ "$HTTP_CODE" = "200" ]; then
        echo -e "${GREEN}✅ API is reachable (HTTP $HTTP_CODE)${NC}"
    else
        echo -e "${RED}❌ API returned HTTP $HTTP_CODE${NC}"
    fi
else
    echo -e "${RED}❌ Cannot reach API server${NC}"
    echo "   Check network connectivity and firewall rules"
fi
rm -f /tmp/http_code.txt
echo ""

# Step 4: Check cache
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "STEP 4: Checking Cache Status"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if php artisan cache:clear > /dev/null 2>&1; then
    echo -e "${GREEN}✅ Cache cleared${NC}"
else
    echo -e "${RED}❌ Failed to clear cache${NC}"
fi
echo ""

# Step 5: Check database connection (if accessible)
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "STEP 5: Database Connection Check"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if php artisan db:show > /dev/null 2>&1; then
    echo -e "${GREEN}✅ Database connection OK${NC}"
else
    echo -e "${YELLOW}⚠️  Could not verify database connection${NC}"
fi
echo ""

# Step 6: Show recent errors summary
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "STEP 6: Error Summary"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if [ -f "storage/logs/laravel.log" ]; then
    ERROR_COUNT=$(tail -n 500 storage/logs/laravel.log | grep -i "error\|exception" | wc -l)
    SYNC_ERROR_COUNT=$(tail -n 500 storage/logs/laravel.log | grep -i "sync.*error\|error.*sync" | wc -l)
    echo "Total errors in last 500 log lines: $ERROR_COUNT"
    echo "Sync-related errors: $SYNC_ERROR_COUNT"
    if [ "$SYNC_ERROR_COUNT" -gt 0 ]; then
        echo -e "${RED}⚠️  Found sync errors! Check logs above.${NC}"
    else
        echo -e "${GREEN}✅ No sync errors found in recent logs${NC}"
    fi
fi
echo ""

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "NEXT STEPS:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "1. If API is unreachable, check AI Pipeline server status"
echo "2. Review Laravel logs above for specific error messages"
echo "3. Test API endpoint directly with curl (see DEBUG_SYNC_ERRORS.md)"
echo "4. Check AI Pipeline logs on server 52.41.213.228"
echo "5. Verify API key is correct in .env file"
echo ""

