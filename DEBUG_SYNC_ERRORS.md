# Debugging Sync Errors in Laravel Dashboard

## Step 1: Check Laravel Logs

**On Laravel Dashboard Server (35.155.88.31):**

```bash
# SSH to Laravel server
ssh -i ~/.ssh/key.pem bitnami@35.155.88.31

# Navigate to project (correct path per PROJECT_ARCHITECTURE.md)
cd /home/bitnami/htdocs/rkmnd.fitform100.net

# Check Laravel logs (most recent errors)
tail -n 100 storage/logs/laravel.log | grep -i "sync\|error\|exception"

# Or view full log
tail -n 200 storage/logs/laravel.log
```

## Step 2: Test API Endpoint Directly

**On Laravel Dashboard Server:**

```bash
# Get your API key from the database or config
# Then test the endpoint directly:

curl -H "Authorization: Bearer YOUR_API_KEY" \
  http://52.41.213.228:8000/api/v1/wordpress/sync/logs?limit=50

# Or if testing locally on AI Pipeline server:
curl -H "Authorization: Bearer YOUR_API_KEY" \
  http://localhost:8000/api/v1/wordpress/sync/logs?limit=50
```

## Step 3: Check AI Pipeline Logs

**On AI Pipeline Server (52.41.213.228):**

```bash
# SSH to AI Pipeline server
ssh -i ~/.ssh/key.pem ubuntu@52.41.213.228

# Navigate to project
cd ~/tuneup_ai_pipeline

# Check most recent daily sync log
ls -lt logs/daily_sync_*.log | head -1 | awk '{print $NF}' | xargs tail -n 100

# Or check all recent logs
tail -n 50 logs/daily_sync_*.log

# Check for errors in logs
grep -i "error\|exception\|failed\|traceback" logs/daily_sync_*.log | tail -20
```

## Step 4: Check Database Directly

**On AI Pipeline Server:**

```bash
# Connect to database
mysql -u root -p ai_pipeline_db

# Check sync logs table
SELECT * FROM wordpress_sync_logs 
ORDER BY started_at DESC 
LIMIT 10;

# Check for failed syncs
SELECT * FROM wordpress_sync_logs 
WHERE status = 'failed' 
ORDER BY started_at DESC 
LIMIT 10;

# Check error messages
SELECT id, sync_type, status, errors_encountered, error_message, started_at 
FROM wordpress_sync_logs 
WHERE errors_encountered > 0 OR status = 'failed'
ORDER BY started_at DESC;
```

## Step 5: Check API Service Logging

**On Laravel Dashboard Server:**

The `BackendApiService` logs errors. Check if there are any connection issues:

```bash
# Check Laravel logs for API errors
grep -i "backend\|api\|sync" storage/logs/laravel.log | tail -20

# Check if API is reachable
curl -I http://52.41.213.228:8000/api/v1/health
```

## Step 6: Test Sync Manually

**On AI Pipeline Server:**

```bash
# Activate virtual environment
source venv/bin/activate

# Run sync manually to see errors in real-time
python3 daily_sync.py --import-only

# Or run full sync
python3 daily_sync.py
```

## Step 7: Check Network Connectivity

**On Laravel Dashboard Server:**

```bash
# Test if Laravel can reach AI Pipeline API
curl -v http://52.41.213.228:8000/api/v1/health

# Check DNS resolution
nslookup 52.41.213.228

# Test with timeout
curl --max-time 10 http://52.41.213.228:8000/api/v1/health
```

## Common Issues & Solutions

### Issue 1: "Unable to load sync logs"
- **Cause**: API connection failure or timeout
- **Check**: Laravel logs, network connectivity, API server status
- **Fix**: Verify API is running, check firewall rules, increase timeout

### Issue 2: "Failed" status in sync logs
- **Cause**: Error during sync process
- **Check**: AI Pipeline logs, database error_message column
- **Fix**: Review error details, fix underlying issue, re-run sync

### Issue 3: Empty response from API
- **Cause**: Authentication failure or empty database
- **Check**: API key validity, database sync_logs table
- **Fix**: Verify API key, check database connection

### Issue 4: Cache showing old data
- **Cause**: Laravel cache not cleared
- **Check**: Cache status
- **Fix**: Run `php artisan cache:clear` on Laravel server

## Quick Debug Script

Save this as `debug_sync.sh` and run it:

```bash
#!/bin/bash
echo "=== Checking Laravel Logs ==="
tail -n 50 storage/logs/laravel.log | grep -i sync

echo -e "\n=== Testing API Endpoint ==="
API_KEY="YOUR_API_KEY_HERE"
curl -H "Authorization: Bearer $API_KEY" \
  http://52.41.213.228:8000/api/v1/wordpress/sync/logs?limit=5

echo -e "\n=== Checking Recent Sync Logs ==="
mysql -u root -p ai_pipeline_db -e "SELECT id, sync_type, status, errors_encountered, started_at FROM wordpress_sync_logs ORDER BY started_at DESC LIMIT 5;"
```

