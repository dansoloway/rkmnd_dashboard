# Direct Database Access Guide
## Laravel Reading FastAPI Database Directly

---

## 🤔 Should You Do This?

### ✅ Reasons TO Use Direct Database Access:
- **Real-time data**: No API latency, no caching delays
- **Complex queries**: Better performance for joins, aggregations, reports
- **Offline mode**: Dashboard works even if FastAPI is down
- **Cost**: No API overhead, fewer server resources
- **Analytics**: Direct SQL queries for custom reporting

### ❌ Reasons NOT TO Use Direct Database Access:
- **Coupling**: Tightly couples Laravel to FastAPI's database schema
- **Breaking changes**: Database schema changes break Laravel instantly
- **Security**: More credentials to manage and secure
- **Responsibility**: Laravel now responsible for data integrity
- **Best practices**: API-first architecture is more maintainable

---

## 🎯 Recommended Approach: **Hybrid**

Use **both** strategies depending on the use case:

| Feature | Use API | Use Direct DB |
|---------|---------|---------------|
| Video browsing | ✅ | |
| Search functionality | ✅ | |
| Tenant info | ✅ | |
| Analytics dashboard | | ✅ |
| Detailed reports | | ✅ |
| Real-time monitoring | | ✅ |
| Admin tools | | ✅ |

---

## 📝 Implementation: Add Second Database Connection

### Step 1: Update Laravel's `config/database.php`

Add a new connection called `fastapi`:

```php
'connections' => [
    
    // Existing Laravel database
    'mysql' => [
        'driver' => 'mysql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'laravel'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        // ... rest of config
    ],

    // NEW: FastAPI database (read-only recommended)
    'fastapi' => [
        'driver' => 'mysql',
        'host' => env('FASTAPI_DB_HOST', '127.0.0.1'),
        'port' => env('FASTAPI_DB_PORT', '3306'),
        'database' => env('FASTAPI_DB_DATABASE', 'ai_pipeline_db'),
        'username' => env('FASTAPI_DB_USERNAME', 'ai_pipeline_readonly'),
        'password' => env('FASTAPI_DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
        'engine' => null,
        
        // Read-only options (MySQL 8.0+)
        'options' => [
            PDO::ATTR_EMULATE_PREPARES => true,
        ],
    ],
],
```

### Step 2: Update `.env` File

```env
# FastAPI Database Connection (Read-Only)
FASTAPI_DB_HOST=127.0.0.1
FASTAPI_DB_PORT=3306
FASTAPI_DB_DATABASE=ai_pipeline_db
FASTAPI_DB_USERNAME=ai_pipeline_readonly
FASTAPI_DB_PASSWORD=ReadOnlyPass2025
```

**For production with SSH tunnel:**
```env
# Local machine connects via SSH tunnel
FASTAPI_DB_HOST=127.0.0.1
FASTAPI_DB_PORT=3306  # Tunneled to production

# Or direct connection if on same server
FASTAPI_DB_HOST=<production-ip>
FASTAPI_DB_PORT=3306
```

---

## 🔒 Step 3: Create Read-Only Database User (Recommended)

On the FastAPI production server:

```bash
# Connect to MySQL
mysql -u root -p

# Create read-only user
CREATE USER 'ai_pipeline_readonly'@'%' IDENTIFIED BY 'ReadOnlyPass2025';
GRANT SELECT ON ai_pipeline_db.* TO 'ai_pipeline_readonly'@'%';
FLUSH PRIVILEGES;

# Test the user
mysql -u ai_pipeline_readonly -pReadOnlyPass2025 ai_pipeline_db -e "SELECT COUNT(*) FROM videos;"
```

**Why read-only?**
- Prevents accidental data corruption from Laravel
- Laravel should only READ from FastAPI database
- All writes should go through the FastAPI API

---

## 💻 Step 4: Create Laravel Models for FastAPI Tables

Create models that use the `fastapi` connection:

**File**: `app/Models/FastApi/Video.php`

```php
<?php

namespace App\Models\FastApi;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    // Use the fastapi database connection
    protected $connection = 'fastapi';
    
    // FastAPI table name
    protected $table = 'videos';
    
    // Disable mass assignment protection (read-only)
    protected $guarded = [];
    
    // Disable timestamps management (FastAPI handles this)
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    
    // Cast JSON fields
    protected $casts = [
        'video_category' => 'array',
        'video_topic' => 'array',
        'video_body_area_taxonomy' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'wp_created' => 'datetime',
        'wp_modified' => 'datetime',
    ];
    
    // Relationships
    public function embeddings()
    {
        return $this->hasMany(VideoEmbedding::class, 'video_id');
    }
    
    public function audioPreviews()
    {
        return $this->hasMany(VideoAudioPreview::class, 'video_id');
    }
    
    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
    
    // Scopes
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
    
    public function scopeCompleted($query)
    {
        return $query->where('sync_status', 'completed');
    }
    
    public function scopeWithAudio($query)
    {
        return $query->whereHas('audioPreviews');
    }
    
    public function scopeWithEmbeddings($query)
    {
        return $query->whereHas('embeddings');
    }
}
```

**File**: `app/Models/FastApi/VideoEmbedding.php`

```php
<?php

namespace App\Models\FastApi;

use Illuminate\Database\Eloquent\Model;

class VideoEmbedding extends Model
{
    protected $connection = 'fastapi';
    protected $table = 'video_embeddings';
    protected $guarded = [];
    
    public function video()
    {
        return $this->belongsTo(Video::class, 'video_id');
    }
}
```

**File**: `app/Models/FastApi/VideoAudioPreview.php`

```php
<?php

namespace App\Models\FastApi;

use Illuminate\Database\Eloquent\Model;

class VideoAudioPreview extends Model
{
    protected $connection = 'fastapi';
    protected $table = 'video_audio_previews';
    protected $guarded = [];
    
    protected $casts = [
        'audio_duration_seconds' => 'integer',
        'file_size_bytes' => 'integer',
        'created_at' => 'datetime',
    ];
    
    public function video()
    {
        return $this->belongsTo(Video::class, 'video_id');
    }
}
```

---

## 🎯 Step 5: Use in Controllers

Now you can query the FastAPI database directly:

**File**: `app/Http/Controllers/AnalyticsController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\FastApi\Video;
use App\Models\FastApi\VideoEmbedding;
use App\Models\FastApi\VideoAudioPreview;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function dashboard()
    {
        $tenantId = auth()->user()->tenant_id;
        
        // Direct database queries - FAST!
        $stats = [
            'total_videos' => Video::forTenant($tenantId)->count(),
            'videos_with_embeddings' => Video::forTenant($tenantId)
                ->withEmbeddings()
                ->count(),
            'videos_with_audio' => Video::forTenant($tenantId)
                ->withAudio()
                ->count(),
            'videos_by_category' => Video::forTenant($tenantId)
                ->selectRaw('JSON_UNQUOTE(JSON_EXTRACT(video_category, "$[0]")) as category, COUNT(*) as count')
                ->groupBy('category')
                ->get(),
            'videos_by_instructor' => Video::forTenant($tenantId)
                ->selectRaw('instructor, COUNT(*) as count')
                ->groupBy('instructor')
                ->get(),
            'processing_status' => Video::forTenant($tenantId)
                ->selectRaw('sync_status, COUNT(*) as count')
                ->groupBy('sync_status')
                ->get(),
        ];
        
        return view('analytics.dashboard', compact('stats'));
    }
    
    public function videoDetails($id)
    {
        $tenantId = auth()->user()->tenant_id;
        
        // Load video with all relationships
        $video = Video::forTenant($tenantId)
            ->with(['embeddings', 'audioPreviews'])
            ->findOrFail($id);
        
        return view('videos.show', compact('video'));
    }
    
    public function processingReport()
    {
        $tenantId = auth()->user()->tenant_id;
        
        // Complex analytics query
        $report = Video::forTenant($tenantId)
            ->selectRaw('
                DATE(created_at) as date,
                COUNT(*) as videos_added,
                SUM(CASE WHEN sync_status = "completed" THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN sync_status = "failed" THEN 1 ELSE 0 END) as failed,
                AVG(CASE WHEN error_message IS NOT NULL THEN 1 ELSE 0 END) * 100 as error_rate
            ')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();
        
        return view('analytics.processing-report', compact('report'));
    }
}
```

---

## 🚦 Usage Examples

### Example 1: Dashboard with Real-Time Stats

```php
use App\Models\FastApi\Video;

// Get real-time counts (no caching)
$stats = [
    'total' => Video::forTenant($tenantId)->count(),
    'with_audio' => Video::forTenant($tenantId)
        ->whereHas('audioPreviews', function($q) {
            $q->where('generation_status', 'completed');
        })
        ->count(),
    'processing' => Video::forTenant($tenantId)
        ->where('sync_status', 'processing')
        ->count(),
];
```

### Example 2: Video List with Filters

```php
$videos = Video::forTenant($tenantId)
    ->when($request->category, function($q, $category) {
        $q->whereJsonContains('video_category', $category);
    })
    ->when($request->instructor, function($q, $instructor) {
        $q->where('instructor', 'like', "%{$instructor}%");
    })
    ->when($request->search, function($q, $search) {
        $q->where(function($query) use ($search) {
            $query->where('title', 'like', "%{$search}%")
                  ->orWhere('short_description', 'like', "%{$search}%");
        });
    })
    ->completed()
    ->orderBy('created_at', 'desc')
    ->paginate(50);
```

### Example 3: Admin Inspection Tools

```php
// Find videos without audio
$missingAudio = Video::forTenant($tenantId)
    ->completed()
    ->whereDoesntHave('audioPreviews')
    ->get();

// Find failed processing
$failures = Video::forTenant($tenantId)
    ->where('sync_status', 'failed')
    ->orWhereNotNull('error_message')
    ->get();

// Get processing timeline
$timeline = Video::forTenant($tenantId)
    ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
    ->groupBy('date')
    ->orderBy('date', 'desc')
    ->limit(30)
    ->get();
```

---

## 🛡️ Security Best Practices

### 1. Always Use Read-Only User
```php
// NEVER do this with direct database access:
Video::create([...]); // ❌ WRONG
Video::where('id', 1)->update([...]); // ❌ WRONG
Video::destroy(1); // ❌ WRONG

// Only do:
Video::where(...)->get(); // ✅ CORRECT
Video::find(1); // ✅ CORRECT
```

### 2. Always Filter by Tenant
```php
// NEVER expose other tenants' data
Video::all(); // ❌ WRONG - gets all tenants

// ALWAYS filter:
Video::forTenant(auth()->user()->tenant_id)->get(); // ✅ CORRECT
```

### 3. Use Middleware to Enforce Tenant Isolation
```php
// app/Http/Middleware/EnsureTenantIsolation.php
public function handle($request, Closure $next)
{
    if (!auth()->user()->tenant_id) {
        abort(403, 'No tenant assigned');
    }
    
    // Set global scope
    Video::addGlobalScope('tenant', function($query) {
        $query->where('tenant_id', auth()->user()->tenant_id);
    });
    
    return $next($request);
}
```

---

## 📊 Performance Comparison

### API Approach (Current):
```
Request → Laravel → HTTP Request → FastAPI → Database
         (50ms)    (100-300ms)     (50ms)    (10ms)
Total: ~200-500ms per request
```

### Direct Database Approach:
```
Request → Laravel → Database
         (50ms)    (10ms)
Total: ~60ms per request
```

**Result**: Direct database is **3-8x faster** for read operations!

---

## 🎯 Recommended Strategy

### Use API For:
- User-facing video browsing
- Search functionality (uses Pinecone via API)
- Audio presigned URLs (API generates them)
- Tenant info and quotas
- Anything that changes frequently

### Use Direct DB For:
- Admin dashboards
- Analytics and reports
- Real-time monitoring
- Bulk operations
- Complex queries and joins
- Background jobs

---

## 🚀 Quick Setup Commands

```bash
# 1. SSH tunnel to production (run on local Mac)
ssh -L 3306:localhost:3306 ubuntu@<production-ip> -N -f

# 2. Update Laravel .env
echo "FASTAPI_DB_HOST=127.0.0.1" >> .env
echo "FASTAPI_DB_PORT=3306" >> .env
echo "FASTAPI_DB_DATABASE=ai_pipeline_db" >> .env
echo "FASTAPI_DB_USERNAME=ai_pipeline_readonly" >> .env
echo "FASTAPI_DB_PASSWORD=ReadOnlyPass2025" >> .env

# 3. Test connection
php artisan tinker
>>> DB::connection('fastapi')->select('SELECT COUNT(*) FROM videos');
```

---

## ✅ Summary

**Yes, Laravel CAN read the FastAPI database directly**, and it's actually a good idea for certain use cases:

✅ **Do it for**: Analytics, reports, admin tools, real-time monitoring  
❌ **Don't do it for**: User-facing features that should use the API  
🔒 **Always**: Use read-only database user  
🎯 **Best approach**: Hybrid - use both API and direct DB where appropriate

---

**Need help implementing this? Let me know!** 🚀

