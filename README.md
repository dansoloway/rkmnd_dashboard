# TuneUp Fitness AI Portal - Laravel Dashboard

**Domain**: `ai.fitform100.net`  
**Backend API**: `https://fitform100.com`  
**Framework**: Laravel 12  
**Purpose**: White-labeled tenant dashboard for video and audio management

---

## 🎯 Project Overview

This Laravel application provides a beautiful, user-friendly interface for TuneUp Fitness tenants to:
- Browse and search their video library
- Run **semantic search** against the same API as WordPress (`POST /api/v1/search`)
- Rate search results with **thumbs up/down** and review feedback on **Analytics**
- Manage **embedding namespaces** (Namespace studio: reconcile, gaps, audio script edits on video detail)
- Listen to AI-generated audio previews
- View usage analytics, recent queries, and quotas
- Manage their account settings

**Production URL:** `https://rkmnd.fitform100.net` (see workspace `ARCHITECTURE.md` for server paths).

The app consumes the FastAPI backend at `https://fitform100.com` and provides a clean separation between the API layer and presentation layer.

---

## 📁 Project Structure

```
/Users/daniel-new2/sites/
├── tuneup_ai_pipeline/          # Python FastAPI Backend
│   └── https://fitform100.com
│
├── tuneup_dash/                 # PHP Internal Analytics Dashboard
│   └── https://fitform100.net
│
└── tuneup_tenant_dashboard/     # Laravel Tenant Portal (THIS PROJECT)
    └── ai.fitform100.net
```

---

## 🚀 Getting Started

### Prerequisites
- PHP 8.2+
- Composer
- MySQL 8.0+

### Installation

1. **Install Dependencies**
```bash
cd /Users/daniel-new2/sites/tuneup_tenant_dashboard
composer install
```

**Note**: This project uses CDN links for frontend libraries, so npm is NOT required.

2. **Configure Environment**
```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:
```env
APP_NAME="TuneUp Fitness AI Portal"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tuneup_fitness
DB_USERNAME=root
DB_PASSWORD=solaR3625

BACKEND_API_URL=https://fitform100.com
BACKEND_API_TIMEOUT=30
TENANT_DEFAULT_API_KEY=K388TLiS1qB0lMDVboXbKYQklZzOWVXC
```

3. **Run Migrations**
```bash
php artisan migrate
```

4. **Start Development Server**
```bash
php artisan serve --port=8000
```

Visit: `http://localhost:8000`

---

## 📚 Documentation

### Essential Reading
1. **Workspace `ARCHITECTURE.md`** — multi-repo map, servers, data flows
2. **`ai_pipeline/tuneup_ai_pipeline/docs/SEARCH_FEEDBACK.md`** — search feedback API and dashboard behavior
3. **`ai_pipeline/tuneup_ai_pipeline/docs/SEARCH_PIPELINE_CURRENT_STATE.md`** — how search scoring and gates work
4. **`LARAVEL_INTEGRATION_GUIDE.md`** / **`API_ENDPOINTS_QUICK_REFERENCE.md`** — in pipeline `docs/`
5. **Backend API Docs** — `https://fitform100.com/docs`

### Search feedback (MVP)

| Page | Route | Notes |
|------|-------|--------|
| Semantic search | `/ai-search` | Playground; 👍/👎 per result or whole query when empty |
| Analytics | `/analytics` | **Search feedback** table + 7/30/90-day filter |
| API proxy | `POST /ai-search/feedback` | Forwards to pipeline `POST /api/v1/search/feedback` |

**Backend service:** `App\Services\BackendApiService::semanticSearchVideos()`, `submitSearchFeedback()`, `getSearchFeedback()`.

**Tests:** `tests/Feature/AiSearchTest.php`, `tests/Feature/AnalyticsTest.php`.

WordPress `page-video-ai.php` does not collect feedback yet.

---

## 🏗️ Features to Build

### Phase 1: Core Features (MVP)
- [ ] **Authentication System**
  - User login/logout
  - Session management
  - API key storage in session
  - Password reset

- [ ] **Video Library**
  - Grid/list view toggle
  - Pagination (50 videos per page)
  - Category filters (Strength, Flexibility, Balance, etc.)
  - Search functionality
  - Sort by date, title, duration

- [ ] **Video Detail Page**
  - Full video information
  - Audio preview player
  - Related videos
  - Share functionality

- [ ] **Analytics Dashboard**
  - Usage statistics charts
  - Quota monitoring
  - Monthly usage trends
  - Top videos viewed

- [ ] **Account Settings**
  - Profile management
  - API key display
  - Email preferences
  - Change password

### Phase 2: Enhanced Features
- [ ] Favorite/bookmark videos
- [ ] Watch history tracking
- [ ] Video recommendations
- [ ] Advanced AI search
- [ ] Download audio previews
- [ ] User activity logs
- [ ] Email notifications

---

## 🎨 Design & Branding

### White-Label Requirements
- **Brand**: TuneUp Fitness
- **Colors**: (To be defined - use TuneUp brand colors)
- **Logo**: TuneUp Fitness logo
- **Fonts**: Professional, clean, modern
- **Style**: Health & fitness oriented, welcoming

### UI/UX Guidelines
- Mobile-first responsive design
- Fast page loads (< 2 seconds)
- Intuitive navigation
- Accessible (WCAG 2.1 AA)
- Clean, minimal interface

---

## 🔧 Key Files to Create

### Services
- `app/Services/BackendApiService.php` - API client
- `app/Services/VideoService.php` - Video business logic
- `app/Services/AudioService.php` - Audio preview handling
- `app/Services/AnalyticsService.php` - Analytics data

### Controllers
- `app/Http/Controllers/Auth/LoginController.php`
- `app/Http/Controllers/DashboardController.php`
- `app/Http/Controllers/VideoController.php`
- `app/Http/Controllers/AnalyticsController.php`
- `app/Http/Controllers/AccountController.php`

### Views (Blade Templates)
- `resources/views/auth/login.blade.php`
- `resources/views/dashboard.blade.php`
- `resources/views/videos/index.blade.php`
- `resources/views/videos/show.blade.php`
- `resources/views/analytics/index.blade.php`
- `resources/views/account/settings.blade.php`

### Models
- `app/Models/Tenant.php`
- `app/Models/User.php`
- `app/Models/CachedVideo.php` (optional)

---

## 🗄️ Database Schema

### Required Tables
```sql
-- Tenants (stores API credentials)
tenants
  - id
  - name (unique)
  - display_name
  - api_key (unique, encrypted)
  - plan_type
  - is_active
  - created_at, updated_at

-- Users (dashboard users)
users
  - id
  - tenant_id (FK)
  - name
  - email (unique)
  - password
  - role
  - created_at, updated_at

-- Cached Videos (optional - for performance)
cached_videos
  - id
  - tenant_id (FK)
  - video_id
  - jwp_id
  - title
  - data (JSON)
  - cached_at
  - expires_at
```

---

## 🔒 Security Checklist

- [ ] Never expose API keys in frontend
- [ ] Use HTTPS only (SSL cert for ai.fitform100.net)
- [ ] Implement CSRF protection
- [ ] Validate all user inputs
- [ ] Sanitize output data
- [ ] Rate limit login attempts
- [ ] Hash passwords with bcrypt
- [ ] Use Laravel's authentication scaffolding
- [ ] Implement session timeout (2 hours)
- [ ] Log security events

---

## 🚀 Deployment

### Production Server Setup

1. **Configure Subdomain**
```
Domain: ai.fitform100.net
Document Root: /path/to/tuneup_tenant_dashboard/public
```

2. **Apache Virtual Host Example**
```apache
<VirtualHost *:443>
    ServerName ai.fitform100.net
    DocumentRoot /path/to/tuneup_tenant_dashboard/public
    
    <Directory /path/to/tuneup_tenant_dashboard/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    SSLEngine on
    SSLCertificateFile /path/to/cert.pem
    SSLCertificateKeyFile /path/to/key.pem
</VirtualHost>
```

3. **Environment Configuration**
```bash
# Production .env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://ai.fitform100.net

BACKEND_API_URL=https://fitform100.com
```

4. **Optimize for Production**
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer install --optimize-autoloader --no-dev
```

5. **Set Permissions**
```bash
chmod -R 755 /path/to/tuneup_tenant_dashboard
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data /path/to/tuneup_tenant_dashboard
```

---

## 🧪 Testing

### API Connection Test
```php
// Test backend API connectivity
php artisan tinker

use App\Services\BackendApiService;
$api = new BackendApiService();
$info = $api->getTenantInfo();
dump($info);
```

### Test Credentials
```
API Key: K388TLiS1qB0lMDVboXbKYQklZzOWVXC
Tenant: test_client
Plan: Pro
```

---

## 📊 Performance Optimization

### Caching Strategy
- Tenant info: 5 minutes
- Video details: 5 minutes
- Video list: 2 minutes
- Stats: 10 minutes
- Presigned URLs: Generate on-demand (1 hour expiry)

### Database Optimization
- Index frequently queried columns
- Use eager loading for relationships
- Implement query result caching
- Use database transactions where appropriate

### Frontend Optimization
- Lazy load images
- Minify CSS/JS
- Use CDN for static assets
- Implement infinite scroll for video lists
- Cache API responses in browser

---

## 🐛 Troubleshooting

### Common Issues

**API Connection Failed**
- Check `BACKEND_API_URL` in `.env`
- Verify API key is valid
- Test with: `curl -H "Authorization: Bearer YOUR_KEY" https://fitform100.com/health`

**Database Connection Failed**
- Verify MySQL is running
- Check database credentials in `.env`
- Ensure database `tuneup_fitness` exists

**Audio Previews Not Playing**
- Check S3 presigned URL generation
- Verify CORS settings on S3 bucket
- Check browser console for errors

---

## 📞 Support

- **Backend Code**: `/Users/daniel-new2/sites/tuneup_ai_pipeline/`
- **Backend API**: `https://fitform100.com`
- **API Docs**: `https://fitform100.com/docs`
- **Integration Guide**: See `tuneup_ai_pipeline/docs/LARAVEL_INTEGRATION_GUIDE.md`

---

## ✅ Development Checklist

### Setup
- [x] Laravel installed
- [x] Environment configured
- [x] Database connected
- [ ] Migrations created
- [ ] Seeders created

### Backend Integration
- [ ] API client service created
- [ ] Authentication working
- [ ] Video endpoints tested
- [ ] Audio preview tested
- [ ] Error handling implemented

### Frontend
- [ ] Authentication UI
- [ ] Dashboard layout
- [ ] Video library
- [ ] Video detail page
- [ ] Analytics dashboard
- [ ] Account settings

### Deployment
- [ ] Subdomain configured
- [ ] SSL certificate installed
- [ ] Production .env configured
- [ ] Caching optimized
- [ ] Tested on production

---

**Created**: October 14, 2025  
**Version**: 1.0.0  
**Status**: Initial Setup Complete - Ready for Development
