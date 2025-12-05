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
echo "STEP 4: Clearing All Caches"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Clearing application cache..."
php artisan cache:clear > /dev/null 2>&1 && echo -e "${GREEN}✅ Application cache cleared${NC}" || echo -e "${RED}❌ Failed to clear cache${NC}"

echo "Clearing route cache..."
php artisan route:clear > /dev/null 2>&1 && echo -e "${GREEN}✅ Route cache cleared${NC}" || echo -e "${YELLOW}⚠️  Route cache already clear${NC}"

echo "Clearing config cache..."
php artisan config:clear > /dev/null 2>&1 && echo -e "${GREEN}✅ Config cache cleared${NC}" || echo -e "${YELLOW}⚠️  Config cache already clear${NC}"

echo "Clearing view cache..."
php artisan view:clear > /dev/null 2>&1 && echo -e "${GREEN}✅ View cache cleared${NC}" || echo -e "${YELLOW}⚠️  View cache already clear${NC}"
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
echo "TESTING API ENDPOINT"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo -e "${YELLOW}Testing sync logs endpoint...${NC}"
API_KEY=$(grep "BACKEND_API_KEY\|TENANT_API_KEY" .env 2>/dev/null | head -1 | cut -d '=' -f2 | tr -d '"' | tr -d "'" | xargs)
if [ -z "$API_KEY" ]; then
    echo -e "${RED}⚠️  Could not find API key in .env${NC}"
    echo "   Please test manually with: curl -H \"Authorization: Bearer YOUR_KEY\" $API_URL/api/v1/wordpress/sync/logs?limit=5"
else
    echo "Testing GET /api/v1/wordpress/sync/logs..."
    RESPONSE=$(curl -s -w "\nHTTP_CODE:%{http_code}" -H "Authorization: Bearer $API_KEY" "$API_URL/api/v1/wordpress/sync/logs?limit=5" 2>&1)
    HTTP_CODE=$(echo "$RESPONSE" | grep "HTTP_CODE" | cut -d':' -f2)
    BODY=$(echo "$RESPONSE" | grep -v "HTTP_CODE")
    
    if [ "$HTTP_CODE" = "200" ]; then
        echo -e "${GREEN}✅ GET endpoint works (HTTP $HTTP_CODE)${NC}"
    else
        echo -e "${RED}❌ GET endpoint failed (HTTP $HTTP_CODE)${NC}"
        echo "Response: $BODY" | head -5
    fi
    
    echo ""
    echo "Testing DELETE /api/v1/wordpress/sync/logs/clear..."
    DELETE_RESPONSE=$(curl -s -w "\nHTTP_CODE:%{http_code}" -X DELETE -H "Authorization: Bearer $API_KEY" "$API_URL/api/v1/wordpress/sync/logs/clear" 2>&1)
    DELETE_CODE=$(echo "$DELETE_RESPONSE" | grep "HTTP_CODE" | cut -d':' -f2)
    DELETE_BODY=$(echo "$DELETE_RESPONSE" | grep -v "HTTP_CODE")
    
    if [ "$DELETE_CODE" = "200" ] || [ "$DELETE_CODE" = "404" ]; then
        if [ "$DELETE_CODE" = "404" ]; then
            echo -e "${RED}❌ DELETE endpoint not found (HTTP 404)${NC}"
            echo -e "${YELLOW}⚠️  This endpoint may not be deployed on the AI Pipeline server${NC}"
            echo "   The endpoint exists in code but may need to be deployed/restarted"
        else
            echo -e "${GREEN}✅ DELETE endpoint works (HTTP $DELETE_CODE)${NC}"
        fi
    else
        echo -e "${RED}❌ DELETE endpoint failed (HTTP $DELETE_CODE)${NC}"
        echo "Response: $DELETE_BODY" | head -5
    fi
fi
echo ""

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "NEXT STEPS:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
if [ "$DELETE_CODE" = "404" ]; then
    echo -e "${YELLOW}⚠️  ACTION REQUIRED:${NC}"
    echo "1. SSH to AI Pipeline server: ssh ubuntu@52.41.213.228"
    echo "2. Navigate: cd ~/tuneup_ai_pipeline"
    echo "3. Pull latest code: git pull"
    echo "4. Restart API server (if using systemd): sudo systemctl restart ai-pipeline"
    echo "   Or restart manually if running in screen/tmux"
    echo ""
fi
echo "1. If API is unreachable, check AI Pipeline server status"
echo "2. Review Laravel logs above for specific error messages"
echo "3. Test API endpoint directly with curl (see DEBUG_SYNC_ERRORS.md)"
echo "4. Check AI Pipeline logs on server 52.41.213.228"
echo "5. Verify API key is correct in .env file"
echo ""

