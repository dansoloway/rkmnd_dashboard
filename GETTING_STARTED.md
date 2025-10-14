# Getting Started with TuneUp Fitness AI Portal

**Welcome!** This guide will help you get the Laravel tenant dashboard up and running.

---

## 📋 Prerequisites

Before you begin, make sure you have:
- ✅ PHP 8.2 or higher
- ✅ Composer installed
- ✅ MySQL 8.0+ running
- ✅ Node.js & NPM
- ✅ Access to the FastAPI backend at `https://fitform100.com`

---

## 🚀 Quick Start (5 Minutes)

### Step 1: Install Dependencies
```bash
cd /Users/daniel-new2/sites/tuneup_tenant_dashboard

# Install PHP dependencies only
composer install
```

**Note**: This project uses CDN links for frontend libraries (Alpine.js, Chart.js, Tailwind CSS), so npm is NOT required.

### Step 2: Configure Environment
The `.env` file is already configured. Verify these settings:
```env
APP_NAME="TuneUp Fitness AI Portal"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=tuneup_fitness
DB_USERNAME=root
DB_PASSWORD=solaR3625

BACKEND_API_URL=https://fitform100.com
BACKEND_API_TIMEOUT=30
TENANT_DEFAULT_API_KEY=K388TLiS1qB0lMDVboXbKYQklZzOWVXC
```

### Step 3: Set Up Database
```bash
# Run migrations (will create tenant and user tables)
php artisan migrate

# Optional: Seed with test data
php artisan db:seed
```

### Step 4: Start Development Server
```bash
php artisan serve --port=8000
```

Visit: **http://localhost:8000** 🎉

---

## 📚 Essential Documentation

### Must Read First
1. **`README.md`** (this directory)
   - Project overview and architecture
   - Complete feature checklist

2. **`LARAVEL_INTEGRATION_GUIDE.md`** (`../tuneup_ai_pipeline/docs/`)
   - **MOST IMPORTANT** - Complete API documentation
   - All endpoints, data models, and examples
   - Implementation patterns and best practices

3. **`API_ENDPOINTS_QUICK_REFERENCE.md`** (`../tuneup_ai_pipeline/docs/`)
   - Quick reference for API calls
   - cURL examples
   - Response formats

### Backend API Documentation
- **Interactive Docs**: https://fitform100.com/docs
- **Health Check**: https://fitform100.com/health/detailed

---

## 🏗️ What to Build (Priority Order)

### 1. Authentication System (Start Here)
Create login functionality:
- `app/Http/Controllers/Auth/LoginController.php`
- `resources/views/auth/login.blade.php`
- Store API key in session upon login
- Implement logout

**Files to create:**
```
app/Http/Controllers/Auth/
  ├── LoginController.php
  └── LogoutController.php

resources/views/auth/
  ├── login.blade.php
  └── layout.blade.php
```

### 2. Backend API Service
Create the API client:
- `app/Services/BackendApiService.php`
- Methods for all API endpoints
- Error handling and caching

**Reference**: See `LARAVEL_INTEGRATION_GUIDE.md` section "Laravel Implementation Guide"

### 3. Dashboard Home
Simple dashboard showing:
- Welcome message
- Quick stats (video count, usage)
- Recent videos
- Navigation menu

**Files to create:**
```
app/Http/Controllers/DashboardController.php
resources/views/dashboard.blade.php
resources/views/layouts/app.blade.php
```

### 4. Video Library
Browse and search videos:
- Grid/list view
- Filters (category, duration, instructor)
- Search bar
- Pagination

**Files to create:**
```
app/Http/Controllers/VideoController.php
resources/views/videos/
  ├── index.blade.php (list view)
  └── _video-card.blade.php (component)
```

### 5. Video Detail Page
Full video information:
- Video metadata
- Audio preview player
- Related videos

**Files to create:**
```
resources/views/videos/show.blade.php
app/Services/AudioService.php (for presigned URLs)
```

### 6. Analytics Dashboard
Usage statistics:
- Charts (use Chart.js or similar)
- Quota monitoring
- Usage trends

**Files to create:**
```
app/Http/Controllers/AnalyticsController.php
resources/views/analytics/index.blade.php
```

### 7. Account Settings
User profile management:
- Display API key (masked)
- Change password
- Email preferences

**Files to create:**
```
app/Http/Controllers/AccountController.php
resources/views/account/settings.blade.php
```

---

## 🎨 Frontend Stack (No NPM Required)

### Server-Side
- **Blade Templates** - Server-side templating
- **Laravel Asset Helper** - Simple `asset()` function for CSS/JS

### Frontend Libraries (via CDN)
- **Tailwind CSS** - `https://cdn.tailwindcss.com`
- **Alpine.js** - For interactivity (dropdowns, modals, etc.)
- **Chart.js** - For analytics charts
- **Your Custom CSS** - `public/css/app.css` [[memory:8437637]]

### Fonts [[memory:8437632]]
- **Assistant** - Default font for content
- **Secular** - Menus and headers

### UI Component Libraries (Optional)
- **Blade UI Kit** - `composer require blade-ui-kit/blade-ui-kit`
- **Livewire** - `composer require livewire/livewire` (for reactive components)

---

## 🧪 Testing Your Setup

### 1. Test API Connection
```bash
php artisan tinker
```

```php
use Illuminate\Support\Facades\Http;

$response = Http::withToken('K388TLiS1qB0lMDVboXbKYQklZzOWVXC')
    ->get('https://fitform100.com/api/v1/tenant/info');

dump($response->json());
```

Expected output:
```php
[
  "id" => 1,
  "name" => "test_client",
  "display_name" => "Test Client",
  "plan_type" => "pro",
  "is_active" => true
]
```

### 2. Test Video Endpoint
```php
$response = Http::withToken('K388TLiS1qB0lMDVboXbKYQklZzOWVXC')
    ->get('https://fitform100.com/api/v1/wordpress/videos?limit=5');

dump($response->json());
```

### 3. Test Health Check
```bash
curl https://fitform100.com/health/detailed
```

---

## 🔧 Development Tips

### Use Laravel Debugbar
```bash
composer require barryvdh/laravel-debugbar --dev
```

### Use IDE Helper
```bash
composer require --dev barryvdh/laravel-ide-helper
php artisan ide-helper:generate
```

### Set Up Git
```bash
git init
git add .
git commit -m "Initial Laravel tenant dashboard setup"
git remote add origin YOUR_REPO_URL
git push -u origin main
```

### Use Laravel Pail for Logs
```bash
php artisan pail
```

---

## 🐛 Common Issues & Solutions

### "Connection refused" when calling API
- Verify `BACKEND_API_URL=https://fitform100.com` in `.env`
- Test with cURL: `curl https://fitform100.com/health`
- Check firewall/network settings

### "Authentication failed" (401)
- Verify API key in `.env`
- Check header format: `Authorization: Bearer {KEY}`
- Test key with cURL

### Database connection failed
- Verify MySQL is running: `mysql.server status`
- Check credentials in `.env`
- Create database: `CREATE DATABASE tuneup_fitness;`

### Frontend asset issues
- Make sure files exist: `public/css/app.css` and `public/js/app.js`
- Check file permissions: `chmod 644 public/css/app.css public/js/app.js`
- CDN not loading? Check internet connection or use local copies

---

## 📞 Need Help?

### Documentation
- Laravel: https://laravel.com/docs
- Backend API: `../tuneup_ai_pipeline/docs/LARAVEL_INTEGRATION_GUIDE.md`
- API Reference: `../tuneup_ai_pipeline/docs/API_ENDPOINTS_QUICK_REFERENCE.md`

### API Testing
- Swagger Docs: https://fitform100.com/docs
- Postman Collections: `../tuneup_ai_pipeline/postman/`

### Code Locations
- Backend (Python): `/Users/daniel-new2/sites/tuneup_ai_pipeline/`
- Dashboard (PHP): `/Users/daniel-new2/sites/tuneup_dash/`
- Tenant Portal (Laravel): `/Users/daniel-new2/sites/tuneup_tenant_dashboard/` (THIS)

---

## ✅ Your First Task

**Create the Backend API Service:**

1. Create file: `app/Services/BackendApiService.php`
2. Copy the service class from `LARAVEL_INTEGRATION_GUIDE.md` (section "Laravel Implementation Guide")
3. Test it works:
   ```bash
   php artisan tinker
   
   use App\Services\BackendApiService;
   $api = new BackendApiService();
   $info = $api->getTenantInfo();
   dump($info);
   ```

Once that works, you're ready to build the UI! 🚀

---

**Happy Coding!** 🎉  
**Created**: October 14, 2025

