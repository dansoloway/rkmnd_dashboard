# 🧪 Test Backend API Connection

This guide shows you how to test if your Laravel dashboard can successfully communicate with the FastAPI backend.

---

## 📋 Quick Test Commands

### Option 1: Using Artisan Command (Recommended)

```bash
# SSH to Laravel server
ssh ubuntu@<laravel-server-ip>

# Navigate to Laravel project
cd /path/to/tuneup_tenant_dashboard

# Run the test command
php artisan test:backend-api
```

---

### Option 2: Using Standalone PHP Script

```bash
# SSH to Laravel server
ssh ubuntu@<laravel-server-ip>

# Navigate to Laravel project
cd /path/to/tuneup_tenant_dashboard

# Run the standalone test script
php test_backend_connection.php
```

---

## 🔧 Prerequisites

Before running tests, ensure:

1. **Environment variables are set** in `.env`:
   ```env
   BACKEND_API_URL=https://fitform100.com
   TENANT_DEFAULT_API_KEY=K388TLiS1qB0lMDVboXbKYQklZzOWVXC
   BACKEND_API_TIMEOUT=30
   ```

2. **FastAPI backend is running** and accessible from Laravel server

3. **Laravel dependencies are installed**:
   ```bash
   composer install
   ```

---

## 📊 What the Tests Check

The test suite will verify:

1. ✅ **Health Check** - Is FastAPI backend online?
2. ✅ **Tenant Info** - Can we retrieve tenant data?
3. ✅ **WordPress Stats** - Can we get video statistics?
4. ✅ **Video List** - Can we fetch video listings?
5. ✅ **Video Detail** - Can we get individual video details?
6. ✅ **S3 Storage Info** - Can we access S3 storage info?
7. ✅ **Semantic Search** - Does AI search work?

---

## 📝 Expected Output

### ✅ Successful Test Run:

```
╔════════════════════════════════════════════════════════════════╗
║        FastAPI Backend Connection Test (via Laravel)          ║
╚════════════════════════════════════════════════════════════════╝

📍 Backend URL: https://fitform100.com
🔑 API Key: K388TLiS1q...

════════════════════════════════════════════════════════════════
RUNNING TESTS
════════════════════════════════════════════════════════════════

Testing: Health Check... ✅ PASSED (125.5ms)
   Status: healthy
   Service: ai-pipeline

Testing: Tenant Info... ✅ PASSED (89.2ms)
   Tenant: test_client (Test Client)
   Plan: pro

Testing: WordPress Stats... ✅ PASSED (156.3ms)
   Total Videos: 508
   With Embeddings: 508
   With Audio: 337

Testing: Video List... ✅ PASSED (178.9ms)
   Retrieved: 10 videos
   First Video: Full Body Strength Workout

Testing: Video Detail... ✅ PASSED (92.1ms)
   Title: Full Body Strength Workout
   Instructor: Miranda Esmonde-White
   Has Audio: Yes

Testing: S3 Storage Info... ✅ PASSED (67.8ms)
   Bucket: tuneup-ai-audio-previews
   Total Files: 337

Testing: Semantic Search... ✅ PASSED (234.5ms)
   Results: 5 videos found

════════════════════════════════════════════════════════════════
RESULTS SUMMARY
════════════════════════════════════════════════════════════════

Total Tests: 7
Passed: 7 ✅
Failed: 0 ❌
Success Rate: 100.0%

╔════════════════════════════════════════════════════════════════╗
║  🎉 ALL TESTS PASSED! Laravel can connect to FastAPI backend  ║
╚════════════════════════════════════════════════════════════════╝
```

---

## 🚨 Troubleshooting Failed Tests

### Error: "Connection refused" or "Timeout"

**Cause**: Laravel server cannot reach FastAPI server

**Solutions**:
1. Check if FastAPI is running:
   ```bash
   curl https://fitform100.com/health/detailed
   ```

2. Verify firewall rules allow connection

3. Check if FastAPI server is listening on correct port:
   ```bash
   # On FastAPI server
   netstat -tulpn | grep 8001
   ```

---

### Error: "401 Unauthorized"

**Cause**: Invalid or missing API key

**Solutions**:
1. Check `.env` file has correct API key:
   ```bash
   grep TENANT_DEFAULT_API_KEY .env
   ```

2. Verify API key exists in FastAPI database:
   ```bash
   # On FastAPI server
   mysql -u ai_pipeline -p ai_pipeline_db -e "SELECT api_key FROM tenants WHERE id=1;"
   ```

3. Update `.env` with correct key and clear config cache:
   ```bash
   php artisan config:clear
   php artisan config:cache
   ```

---

### Error: "404 Not Found"

**Cause**: FastAPI endpoints changed or incorrect URL

**Solutions**:
1. Verify backend URL:
   ```bash
   echo $BACKEND_API_URL
   ```

2. Test endpoint manually:
   ```bash
   curl -H "Authorization: Bearer YOUR_API_KEY" \
        https://fitform100.com/api/v1/tenant/info
   ```

3. Check API routing in FastAPI:
   ```bash
   # On FastAPI server
   grep -r "api/v1/tenant" app/
   ```

---

### Error: "SSL Certificate" issues

**Cause**: SSL certificate not trusted

**Solutions**:
1. If using self-signed cert in development:
   ```php
   // Temporarily in test script (NOT for production!)
   curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
   ```

2. Install proper SSL certificate on FastAPI server

3. Update CA certificates on Laravel server:
   ```bash
   sudo apt-get update
   sudo apt-get install ca-certificates
   ```

---

## 🎯 Manual Endpoint Testing

If automated tests fail, test endpoints manually:

### Test 1: Health Check
```bash
curl https://fitform100.com/health/detailed
```

### Test 2: Tenant Info
```bash
curl -H "Authorization: Bearer K388TLiS1qB0lMDVboXbKYQklZzOWVXC" \
     https://fitform100.com/api/v1/tenant/info
```

### Test 3: Video List
```bash
curl -H "Authorization: Bearer K388TLiS1qB0lMDVboXbKYQklZzOWVXC" \
     https://fitform100.com/api/v1/wordpress/videos?limit=5
```

### Test 4: Search
```bash
curl -X POST https://fitform100.com/api/v1/search/semantic \
     -H "Authorization: Bearer K388TLiS1qB0lMDVboXbKYQklZzOWVXC" \
     -H "Content-Type: application/json" \
     -d '{"query": "yoga", "limit": 5}'
```

---

## 🔍 Verify Network Connectivity

### From Laravel Server to FastAPI:

```bash
# Test if FastAPI server is reachable
ping <fastapi-server-ip>

# Test if port 443 (HTTPS) is accessible
telnet fitform100.com 443

# Or use nc (netcat)
nc -zv fitform100.com 443

# Full HTTP test
curl -I https://fitform100.com/health/detailed
```

---

## 📂 Test Files Created

1. **`test_backend_connection.php`**
   - Standalone PHP script
   - No Laravel dependencies required
   - Quick test using cURL

2. **`app/Console/Commands/TestBackendApi.php`**
   - Laravel Artisan command
   - Uses BackendApiService
   - More integrated with Laravel

---

## ✅ After Tests Pass

Once all tests pass, you can:

1. **Build dashboard pages** using `BackendApiService`
2. **Access data** in controllers:
   ```php
   $api = new BackendApiService();
   $videos = $api->getVideos(['limit' => 50]);
   ```

3. **Test in browser** by visiting dashboard routes

---

## 🚀 Next Steps

After successful connection test:

1. Create video listing page
2. Build video detail page with audio player
3. Implement search functionality
4. Add analytics dashboard
5. Deploy to production domain

---

## 📞 Support

If tests continue to fail after troubleshooting:

1. Check FastAPI logs:
   ```bash
   tail -f /home/ubuntu/tuneup_ai_pipeline/logs/errors.log
   ```

2. Check Laravel logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. Enable debug mode in Laravel `.env`:
   ```env
   APP_DEBUG=true
   LOG_LEVEL=debug
   ```

---

**Last Updated**: October 20, 2025

