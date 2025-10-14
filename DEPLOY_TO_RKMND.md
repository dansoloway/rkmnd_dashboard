# 🚀 Deploy to rkmnd.fitform100.net

**Quick Start Guide**

---

## ✅ Ready to Deploy!

Your Laravel tenant dashboard is ready to deploy to:
**https://rkmnd.fitform100.net**

---

## 📋 Pre-Flight Check

- [ ] You have SSH access to fitform100.net server
- [ ] You have DNS access to add subdomain
- [ ] MySQL is running on the server
- [ ] Apache or Nginx is configured

---

## 🎯 Step-by-Step Deployment

### 1. Add DNS Record (Do First!)

Login to your DNS provider and add:

```
Type: A (or CNAME)
Name: rkmnd
Value: [Your Server IP] or fitform100.net
TTL: 300
```

**Wait 5-10 minutes** for DNS to propagate.

Test with: `ping rkmnd.fitform100.net`

---

### 2. SSH to Your Server

```bash
ssh user@fitform100.net
```

---

### 3. Clone Repository

```bash
cd /var/www/html
git clone https://github.com/dansoloway/rkmnd_dashboard.git rkmnd.fitform100.net
cd rkmnd.fitform100.net
```

---

### 4. Install Dependencies

```bash
composer install --no-dev --optimize-autoloader
```

**Note**: No npm needed! Frontend uses CDN.

---

### 5. Configure Environment

```bash
cp .env.example .env
nano .env
```

Update these values:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://rkmnd.fitform100.net

DB_DATABASE=tuneup_fitness
DB_USERNAME=your_db_user
DB_PASSWORD=your_secure_password

# DO NOT include default API key in production
```

Generate key:
```bash
php artisan key:generate
```

---

### 6. Set Permissions

```bash
sudo chown -R www-data:www-data /var/www/html/rkmnd.fitform100.net
sudo chmod -R 775 storage bootstrap/cache
```

---

### 7. Create Database

```bash
mysql -u root -p
```

```sql
CREATE DATABASE tuneup_fitness CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

Run migrations:
```bash
php artisan migrate --force
```

---

### 8. Configure Apache

Create: `/etc/apache2/sites-available/rkmnd.fitform100.net.conf`

```apache
<VirtualHost *:80>
    ServerName rkmnd.fitform100.net
    DocumentRoot /var/www/html/rkmnd.fitform100.net/public
    
    <Directory /var/www/html/rkmnd.fitform100.net/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/rkmnd.fitform100.net-error.log
    CustomLog ${APACHE_LOG_DIR}/rkmnd.fitform100.net-access.log combined
</VirtualHost>
```

Enable:
```bash
sudo a2ensite rkmnd.fitform100.net.conf
sudo a2enmod rewrite
sudo systemctl reload apache2
```

---

### 9. Add SSL Certificate

```bash
sudo certbot --apache -d rkmnd.fitform100.net
```

Choose: **Redirect HTTP to HTTPS**

---

### 10. Optimize

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## ✅ Test Your Deployment

Visit: **https://rkmnd.fitform100.net**

You should see the login page!

Test login:
- Email: `admin@testclient.com`
- Password: `password`

---

## 🔄 Future Updates

When you push changes to GitHub, update the server:

```bash
cd /var/www/html/rkmnd.fitform100.net
./deploy.sh
```

Or manually:
```bash
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
```

---

## 🆘 Troubleshooting

**Can't access site:**
- Check DNS: `nslookup rkmnd.fitform100.net`
- Check Apache: `sudo systemctl status apache2`
- Check logs: `tail -f storage/logs/laravel.log`

**500 Error:**
```bash
sudo chmod -R 775 storage bootstrap/cache
php artisan cache:clear
```

**Database error:**
- Check `.env` credentials
- Verify database exists: `mysql -u root -p -e "SHOW DATABASES;"`

---

## 📞 Need Help?

- **Full Guide**: See `DEPLOYMENT_GUIDE.md`
- **Quick Ref**: See `QUICK_DEPLOY.md`
- **GitHub**: https://github.com/dansoloway/rkmnd_dashboard
- **Local Test**: http://localhost:8005

---

## 🎉 Summary

**What You're Deploying:**
- Complete Laravel 12 tenant dashboard
- Backend API integration (FastAPI at fitform100.com)
- Beautiful UI (Tailwind via CDN, no npm!)
- Authentication system
- Video library with filters
- Analytics dashboard
- Account management
- Mobile responsive

**Target URL:** https://rkmnd.fitform100.net  
**Repository:** https://github.com/dansoloway/rkmnd_dashboard  
**Status:** ✅ Ready to Deploy!

---

**Start at Step 1 above and go!** 🚀

