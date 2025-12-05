# Quick Fix for Sync Errors

## Issue Summary

Two errors were found:
1. **Laravel Route Error**: `Route [sync-logs.clear] not defined`
2. **Backend API 404**: `/api/v1/wordpress/sync/logs/clear` returns 404

## Fix Steps

### Step 1: Clear Laravel Caches (On Laravel Dashboard Server)

```bash
# SSH to Laravel Dashboard server
ssh bitnami@35.155.88.31

# Navigate to project
cd /home/bitnami/htdocs/rkmnd.fitform100.net

# Clear all caches
php artisan cache:clear
php artisan route:clear
php artisan config:clear
php artisan view:clear

# Verify routes are loaded
php artisan route:list | grep sync-logs
```

You should see:
```
sync-logs.index    GET|HEAD  /sync-logs
sync-logs.clear    POST      /sync-logs/clear
```

### Step 2: Deploy Latest Code to AI Pipeline Server

The `clear_sync_logs` endpoint exists in the code but may not be deployed:

```bash
# SSH to AI Pipeline server
ssh ubuntu@52.41.213.228

# Navigate to project
cd ~/tuneup_ai_pipeline

# Pull latest code
git pull

# Activate virtual environment
source venv/bin/activate

# Verify the endpoint exists in code
grep -n "clear_sync_logs" app/api/v1/endpoints/wordpress.py

# Restart the API server
# If using systemd:
sudo systemctl restart ai-pipeline

# Or if running manually (screen/tmux):
# Find the process and restart it
ps aux | grep uvicorn
# Kill and restart, or use your deployment script
```

### Step 3: Verify API Endpoint Works

```bash
# On AI Pipeline server, test locally:
curl -X DELETE -H "Authorization: Bearer YOUR_API_KEY" \
  http://localhost:8000/api/v1/wordpress/sync/logs/clear

# Should return:
# {"status":"success","message":"Cleared X sync log(s)","deleted_count":X}
```

### Step 4: Test from Laravel Dashboard

After completing steps 1-3:
1. Refresh the sync logs page in the browser
2. Try clicking "Clear All Logs" again
3. Check Laravel logs if it still fails: `tail -f storage/logs/laravel.log`

## Alternative: Temporary Workaround

If you can't deploy immediately, you can manually clear sync logs via database:

```bash
# On AI Pipeline server
mysql -u root -p ai_pipeline_db

# Clear sync logs for tenant 1 (adjust tenant_id as needed)
DELETE FROM wordpress_sync_logs WHERE tenant_id = 1;

# Or clear all
TRUNCATE TABLE wordpress_sync_logs;
```

## Verification

After fixes, run the debug script again:

```bash
cd /home/bitnami/htdocs/rkmnd.fitform100.net
./debug_sync.sh
```

All checks should pass:
- ✅ Route cache cleared
- ✅ API endpoint works (HTTP 200)
- ✅ No sync errors in logs

