# 🚀 Deployment Guide - ai.fitform100.net

**Target Domain**: `ai.fitform100.net`  
**Parent Domain**: `fitform100.net`  
**Framework**: Laravel 12  
**Date**: October 14, 2025

---

## 📋 Pre-Deployment Checklist

- [ ] Server access (SSH)
- [ ] Domain DNS access
- [ ] SSL certificate (Let's Encrypt recommended)
- [ ] PHP 8.2+ installed on server
- [ ] Composer installed on server
- [ ] MySQL database created
- [ ] Web server (Apache/Nginx) configured

---

## 🌐 Step 1: DNS Configuration

### Add Subdomain A Record

In your DNS settings for `fitform100.net`:

```
Type: A
Name: ai
Value: [Your Server IP Address]
TTL: 300 (or Auto)
```

**Or CNAME if pointing to existing domain:**
```
Type: CNAME
Name: ai
Value: fitform100.net
TTL: 300
```

**Wait 5-30 minutes** for DNS propagation.

**Test DNS:**
```bash
ping ai.fitform100.net
nslookup ai.fitform100.net
```

---

## 📦 Step 2: Deploy Code to Server

### Option A: Clone from GitHub
```bash
# SSH into your server
ssh user@fitform100.net

# Navigate to web root (adjust path as needed)
cd /var/www/html

# Clone the repository
git clone https://github.com/dansoloway/rkmnd_dashboard.git ai.fitform100.net

# Enter directory
cd ai.fitform100.net
```

### Option B: Upload via FTP/SFTP
1. Connect to your server via FTP/SFTP
2. Upload all files to `/var/www/html/ai.fitform100.net/`
3. Ensure proper permissions

---

## 🔧 Step 3: Server Configuration

### Install Dependencies
```bash
cd /var/www/html/ai.fitform100.net

# Install Composer dependencies (production)
composer install --optimize-autoloader --no-dev

# Note: No npm needed! We use CDN for frontend
```

### Set Up Environment File
```bash
# Copy example env file
cp .env.example .env

# Edit environment file
nano .env
```

### Configure Production `.env`:
```env
APP_NAME="TuneUp Fitness AI Portal"
APP_ENV=production
APP_KEY=base64:YOUR_KEY_HERE
APP_DEBUG=false
APP_URL=https://ai.fitform100.net

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tuneup_fitness
DB_USERNAME=your_db_user
DB_PASSWORD=your_secure_password

# Session
SESSION_DRIVER=database
SESSION_LIFETIME=120

# Cache
CACHE_STORE=database

# Backend API
BACKEND_API_URL=https://fitform100.com
BACKEND_API_TIMEOUT=30

# DO NOT include default API key in production
# Users will use their tenant's API key
```

### Generate Application Key
```bash
php artisan key:generate
```

### Set Permissions
```bash
# Set ownership (adjust user/group as needed)
sudo chown -R www-data:www-data /var/www/html/ai.fitform100.net

# Set directory permissions
sudo find /var/www/html/ai.fitform100.net -type d -exec chmod 755 {} \;

# Set file permissions
sudo find /var/www/html/ai.fitform100.net -type f -exec chmod 644 {} \;

# Storage and cache need write access
sudo chmod -R 775 /var/www/html/ai.fitform100.net/storage
sudo chmod -R 775 /var/www/html/ai.fitform100.net/bootstrap/cache
```

---

## 🗄️ Step 4: Database Setup

### Create Database
```bash
# Login to MySQL
mysql -u root -p

# Create database
CREATE DATABASE tuneup_fitness CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Create user (recommended)
CREATE USER 'tuneup_user'@'localhost' IDENTIFIED BY 'secure_password_here';
GRANT ALL PRIVILEGES ON tuneup_fitness.* TO 'tuneup_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Run Migrations
```bash
cd /var/www/html/ai.fitform100.net

# Run migrations
php artisan migrate --force

# Seed initial data (optional - creates test users)
# php artisan db:seed --force
```

---

## 🌍 Step 5: Web Server Configuration

### Option A: Apache Virtual Host

Create file: `/etc/apache2/sites-available/ai.fitform100.net.conf`

```apache
<VirtualHost *:80>
    ServerName ai.fitform100.net
    ServerAdmin admin@fitform100.net
    DocumentRoot /var/www/html/ai.fitform100.net/public

    <Directory /var/www/html/ai.fitform100.net/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/ai.fitform100.net-error.log
    CustomLog ${APACHE_LOG_DIR}/ai.fitform100.net-access.log combined
</VirtualHost>
```

**Enable site:**
```bash
# Enable the site
sudo a2ensite ai.fitform100.net.conf

# Enable mod_rewrite (if not already enabled)
sudo a2enmod rewrite

# Test Apache configuration
sudo apache2ctl configtest

# Reload Apache
sudo systemctl reload apache2
```

### Option B: Nginx Configuration

Create file: `/etc/nginx/sites-available/ai.fitform100.net`

```nginx
server {
    listen 80;
    server_name ai.fitform100.net;
    root /var/www/html/ai.fitform100.net/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

**Enable site:**
```bash
# Create symlink
sudo ln -s /etc/nginx/sites-available/ai.fitform100.net /etc/nginx/sites-enabled/

# Test Nginx configuration
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
```

---

## 🔒 Step 6: SSL Certificate (HTTPS)

### Using Let's Encrypt (Recommended)

```bash
# Install Certbot (if not already installed)
sudo apt update
sudo apt install certbot python3-certbot-apache  # For Apache
# OR
sudo apt install certbot python3-certbot-nginx   # For Nginx

# Get SSL certificate
sudo certbot --apache -d ai.fitform100.net    # For Apache
# OR
sudo certbot --nginx -d ai.fitform100.net     # For Nginx

# Follow the prompts
# Choose to redirect HTTP to HTTPS (recommended)

# Test auto-renewal
sudo certbot renew --dry-run
```

**Certbot will automatically:**
- Create SSL certificate
- Configure your web server
- Set up auto-renewal

### Manual SSL Configuration (if needed)

If you have an existing SSL certificate, update your virtual host:

**Apache:**
```apache
<VirtualHost *:443>
    ServerName ai.fitform100.net
    DocumentRoot /var/www/html/ai.fitform100.net/public

    SSLEngine on
    SSLCertificateFile /path/to/cert.pem
    SSLCertificateKeyFile /path/to/key.pem
    SSLCertificateChainFile /path/to/chain.pem

    <Directory /var/www/html/ai.fitform100.net/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

---

## ⚡ Step 7: Optimize for Production

```bash
cd /var/www/html/ai.fitform100.net

# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize
```

---

## 🧪 Step 8: Test Deployment

### Test Checklist:

1. **Visit domain**: https://ai.fitform100.net
   - [ ] Page loads without errors
   - [ ] HTTPS is active (green lock)
   - [ ] CSS/JS loads from CDN

2. **Test Login**
   - [ ] Login page displays correctly
   - [ ] Can log in with test credentials
   - [ ] Session persists

3. **Test Features**
   - [ ] Dashboard loads
   - [ ] Navigation works
   - [ ] Analytics page shows data
   - [ ] Account settings work
   - [ ] Mobile responsive

4. **Test API Connection**
   ```bash
   # SSH into server
   cd /var/www/html/ai.fitform100.net
   php artisan backend:test
   ```

---

## 🔄 Step 9: Create Production Users

### Option 1: Via Tinker (SSH)
```bash
cd /var/www/html/ai.fitform100.net
php artisan tinker
```

```php
// Create tenant
$tenant = \App\Models\Tenant::create([
    'name' => 'production_client',
    'display_name' => 'Production Client',
    'api_key' => 'YOUR_PRODUCTION_API_KEY',
    'plan_type' => 'pro',
    'is_active' => true,
]);

// Create admin user
\App\Models\User::create([
    'tenant_id' => $tenant->id,
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'password' => bcrypt('secure_password_here'),
    'role' => 'admin',
]);
```

### Option 2: Via Database
Manually insert into database using phpMyAdmin or MySQL client.

---

## 📊 Step 10: Monitoring & Maintenance

### Set Up Logs
```bash
# View Laravel logs
tail -f /var/www/html/ai.fitform100.net/storage/logs/laravel.log

# View web server logs
tail -f /var/log/apache2/ai.fitform100.net-error.log  # Apache
tail -f /var/log/nginx/error.log                      # Nginx
```

### Set Up Cron (for scheduled tasks)
```bash
# Edit crontab
crontab -e

# Add Laravel scheduler
* * * * * cd /var/www/html/ai.fitform100.net && php artisan schedule:run >> /dev/null 2>&1
```

### Regular Maintenance
```bash
# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Pull latest changes from git
git pull origin main

# Update dependencies (if needed)
composer install --no-dev --optimize-autoloader

# Re-cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 🔐 Security Checklist

- [ ] `.env` file has correct production values
- [ ] `APP_DEBUG=false` in production
- [ ] Database credentials are secure
- [ ] SSL certificate is active
- [ ] File permissions are correct (755 directories, 644 files)
- [ ] Storage and cache directories are writable
- [ ] `.git` directory is not web-accessible
- [ ] Remove test credentials from seeders
- [ ] Set up regular backups
- [ ] Configure firewall rules
- [ ] Keep Laravel and PHP updated

---

## 🚨 Troubleshooting

### Common Issues:

**500 Internal Server Error**
```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Check permissions
sudo chmod -R 775 storage bootstrap/cache

# Clear cache
php artisan cache:clear
php artisan config:clear
```

**404 Errors on routes**
```bash
# Enable mod_rewrite (Apache)
sudo a2enmod rewrite
sudo systemctl restart apache2

# Check .htaccess exists in public/
ls -la public/.htaccess
```

**CSS/JS not loading**
- Check if CDN links are accessible
- Check browser console for errors
- Verify `APP_URL` in `.env` is correct

**Database connection failed**
- Verify database credentials in `.env`
- Test MySQL connection
- Check if database user has proper permissions

---

## 📞 Support Commands

### Quick Diagnostics
```bash
# Check PHP version
php -v

# Check Laravel version
php artisan --version

# Check environment
php artisan env

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check routes
php artisan route:list
```

---

## 🎉 Post-Deployment

Once deployed successfully:

1. **Update GitHub with production notes**
   ```bash
   # Locally, update README with production URL
   git add .
   git commit -m "Updated with production deployment info"
   git push
   ```

2. **Create backup strategy**
   - Database backups (daily)
   - File backups (weekly)
   - Git repository (already done)

3. **Monitor performance**
   - Set up uptime monitoring
   - Monitor disk space
   - Monitor database size

4. **Document credentials**
   - Keep `.env` backup in secure location
   - Document database credentials
   - Document admin user accounts

---

**Deployment URL**: https://ai.fitform100.net  
**Repository**: https://github.com/dansoloway/rkmnd_dashboard  
**Status**: Ready to Deploy! 🚀

