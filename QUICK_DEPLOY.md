# ⚡ Quick Deployment Guide - ai.fitform100.net

**5-Minute Setup Summary** (Full guide: DEPLOYMENT_GUIDE.md)

---

## 🎯 Pre-Requirements

- [ ] Server access to fitform100.net
- [ ] DNS access to add subdomain
- [ ] MySQL database credentials
- [ ] SSL certificate capability (Let's Encrypt)

---

## 🚀 Quick Steps

### 1. DNS Setup (2 minutes)
Add A record in your DNS:
```
Type: A
Name: ai
Value: [Your Server IP]
```
Wait ~5 minutes for propagation.

### 2. SSH to Server
```bash
ssh user@fitform100.net
cd /var/www/html
```

### 3. Clone Repository
```bash
git clone https://github.com/dansoloway/rkmnd_dashboard.git ai.fitform100.net
cd ai.fitform100.net
```

### 4. Install & Configure
```bash
# Install dependencies
composer install --no-dev --optimize-autoloader

# Copy and edit environment
cp .env.example .env
nano .env  # Update: APP_URL, DB credentials, remove default API key

# Generate key
php artisan key:generate

# Set permissions
sudo chown -R www-data:www-data .
sudo chmod -R 775 storage bootstrap/cache
```

### 5. Database Setup
```bash
# Create database
mysql -u root -p
> CREATE DATABASE tuneup_fitness CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
> EXIT;

# Run migrations
php artisan migrate --force
```

### 6. Web Server Config

**Apache:**
```bash
# Create config
sudo nano /etc/apache2/sites-available/ai.fitform100.net.conf
```

Paste:
```apache
<VirtualHost *:80>
    ServerName ai.fitform100.net
    DocumentRoot /var/www/html/ai.fitform100.net/public
    <Directory /var/www/html/ai.fitform100.net/public>
        AllowOverride All
    </Directory>
</VirtualHost>
```

Enable:
```bash
sudo a2ensite ai.fitform100.net.conf
sudo a2enmod rewrite
sudo systemctl reload apache2
```

### 7. SSL Certificate
```bash
sudo certbot --apache -d ai.fitform100.net
# Choose: Redirect HTTP to HTTPS
```

### 8. Optimize
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 9. Test
Visit: **https://ai.fitform100.net**

---

## 🔄 Future Updates

Use the automated script:
```bash
cd /var/www/html/ai.fitform100.net
./deploy.sh
```

Or manually:
```bash
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan cache:clear
php artisan config:cache
php artisan route:cache
```

---

## 🆘 Quick Troubleshooting

**500 Error:**
```bash
tail -f storage/logs/laravel.log
sudo chmod -R 775 storage bootstrap/cache
```

**404 on routes:**
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

**Database error:**
```bash
# Check .env file has correct credentials
php artisan tinker
>>> DB::connection()->getPdo();
```

---

## 📞 Support

- Full Guide: `DEPLOYMENT_GUIDE.md`
- GitHub: https://github.com/dansoloway/rkmnd_dashboard
- Local Test: http://localhost:8005

---

**Target**: https://ai.fitform100.net  
**Status**: Ready to Deploy! 🚀

