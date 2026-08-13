# 👤 User Setup Guide

Quick guide to create users for the Laravel dashboard.

---

## ✅ Preferred: Users UI (admins)

1. Log in as an **admin** or **superadmin** at https://rkmnd.fitform100.net/login
2. Open **Platform → Users**
3. Create users with role **Analytics only**, **Manager**, or **Admin**

If your account is still `user`, promote it once via artisan (see below), then use the UI.

---

## 🚀 Quick Setup (Run on Laravel Server)

```bash
# Navigate to project
cd /home/bitnami/htdocs/rkmnd.fitform100.net

# Run migrations (if not already done)
php artisan migrate

# Create a test user
php artisan user:create-test
```

The command will prompt you for:
- Email address (default: admin@test.com)
- Password (default: password)
- Full name (default: Test Admin)

---

## 📝 Manual User Creation

If you prefer to create a user manually:

```bash
php artisan user:create-test \
  --email="your.email@example.com" \
  --password="YourSecurePassword123" \
  --name="Your Name"
```

---

## 🔧 If You Get Errors

### Error: "No tenant found in database"

Create a tenant first:

```bash
php artisan tinker
```

Then in tinker:
```php
App\Models\Tenant::create([
    'name' => 'test_client',
    'api_key' => 'K388TLiS1qB0lMDVboXbKYQklZzOWVXC'
]);
exit
```

Then create the user:
```bash
php artisan user:create-test
```

---

### Error: "Table 'users' doesn't exist"

Run migrations:
```bash
php artisan migrate
```

If you get permission errors:
```bash
# Fix database file permissions
sudo chown -R bitnami:bitnami storage/
sudo chmod -R 775 storage/
```

---

## 🌐 Login to Dashboard

After creating the user:

1. **Visit**: `https://rkmnd.fitform100.net/login`
2. **Email**: The email you entered
3. **Password**: The password you entered

---

## 👥 Create Multiple Users

```bash
# Create admin user (full dashboard)
php artisan user:create-test \
  --email="admin@fitform100.com" \
  --password="SecurePassword123" \
  --name="Admin User" \
  --role=admin

# Create a manager (full dashboard, including anatomy terms)
php artisan user:create-test \
  --email="user@fitform100.com" \
  --password="UserPassword123" \
  --name="Regular Manager" \
  --role=manager

# Analytics-only (queries + results; no library/playground/vocab)
php artisan user:create-test \
  --email="manager@example.com" \
  --password="SecurePassword123" \
  --name="Analytics Manager" \
  --role=analytics
```

### Roles

| Role (stored) | Display name | Access |
|---------------|--------------|--------|
| `user` (default) | **Manager** | Full dashboard, including anatomy dictionary and catalog terms |
| `admin` / `superadmin` | Admin / Superadmin | Full dashboard + user management |
| `analytics` | Analytics only | AI Search Analytics + Account only |

---

## 🔑 Reset a password

People can click **Forgot password?** on the login page. Admins can also open **Platform → Users → Edit** and click **Send password reset email**.

Both send a reset link to that person’s email. Mail must be configured (`MAIL_MAILER` — not `log`) or the message only appears in the Laravel log.

To set a password from the server instead:

```bash
php artisan user:create-test --email="existing@email.com"
```

Choose "yes" to update the password.

---

## 🗄️ Database Direct Access (Alternative)

If artisan commands don't work, create user directly in database:

```bash
mysql -u root -p
```

```sql
USE laravel_database;  -- Replace with your database name

-- Create a tenant if needed
INSERT INTO tenants (name, api_key, created_at, updated_at) 
VALUES ('test_client', 'K388TLiS1qB0lMDVboXbKYQklZzOWVXC', NOW(), NOW());

-- Create a user (password is 'password' hashed)
INSERT INTO users (name, email, password, tenant_id, created_at, updated_at) 
VALUES (
    'Test Admin',
    'admin@test.com',
    '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5NJ0kVCi9FH7e',
    1,
    NOW(),
    NOW()
);
```

**Note**: That hashed password is for the string "password"

---

## ✅ Verify User Creation

```bash
php artisan tinker
```

```php
// Check if user exists
User::where('email', 'admin@test.com')->first();

// See all users
User::all();

// See all tenants
App\Models\Tenant::all();
```

---

## 🎯 Default Credentials

After running `php artisan user:create-test` with defaults:

```
Email:    admin@test.com
Password: password
```

**⚠️  IMPORTANT**: Change this password in production!

---

## 📊 Check User Status

```bash
php artisan tinker
```

```php
$user = User::where('email', 'admin@test.com')->first();

echo "Name: " . $user->name . "\n";
echo "Email: " . $user->email . "\n";
echo "Tenant ID: " . $user->tenant_id . "\n";
echo "Tenant: " . $user->tenant->name . "\n";
```

---

## 🔍 Troubleshooting

### "Cannot login - credentials incorrect"

1. **Check user exists**:
   ```bash
   php artisan tinker
   User::where('email', 'YOUR_EMAIL')->first()
   ```

2. **Reset password**:
   ```bash
   php artisan user:create-test --email="YOUR_EMAIL" --password="NEW_PASSWORD"
   ```

3. **Clear cache**:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   ```

---

### "Session not working"

```bash
# Generate new app key if needed
php artisan key:generate

# Fix session permissions
sudo chown -R bitnami:daemon storage/framework/sessions
sudo chmod -R 775 storage/framework/sessions
```

---

### "CSRF token mismatch"

```bash
# Clear config
php artisan config:clear

# Make sure APP_KEY is set in .env
grep APP_KEY .env

# If empty, generate one
php artisan key:generate
```

---

## 🚀 Quick Reference

```bash
# Create user (interactive)
php artisan user:create-test

# Create user (non-interactive)
php artisan user:create-test \
  --email="admin@test.com" \
  --password="password" \
  --name="Admin"

# List all users
php artisan tinker
>>> User::all()

# Delete a user
php artisan tinker
>>> User::where('email', 'admin@test.com')->delete()
```

---

**Ready to login!** 🎉

