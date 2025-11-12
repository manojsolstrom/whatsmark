# WhatsApp Marketing Application - Complete Installation Guide

## 🚀 Fresh Installation Instructions

### Prerequisites
- Cloudways server with PHP 8.1+
- MySQL database
- SSH access to your server

### Step 1: Clean Installation on Server

```bash
# SSH to your server
ssh master@your-server-ip -p your-port

# Navigate to your web directory
cd /home/1548555.cloudwaysapps.com/hewbhsdagx/public_html

# Remove all existing files (if any)
rm -rf * .*

# Clone the repository
git clone https://github.com/manojsolstrom/whatsmark.git .

# Run the fresh installation script
chmod +x fresh-install.sh
./fresh-install.sh
```

### Step 2: Configure Database

```bash
# Edit environment file
nano .env

# Update these database settings (get from Cloudways panel):
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_actual_db_name
DB_USERNAME=your_actual_db_user
DB_PASSWORD=your_actual_db_password
```

### Step 3: Run Database Migrations

```bash
# Run migrations to create all tables
php artisan migrate --force

# Run seeders (optional)
php artisan db:seed --force
```

### Step 4: Enable PHP Extensions (Cloudways Panel)

1. Go to **Cloudways Panel**
2. Navigate to **Server Management → Settings & Packages**
3. Click on **Package/Extensions**
4. Enable these extensions:
   - `tokenizer`
   - `fileinfo` 
   - `xml`
   - `dom`
   - `mbstring`
   - `openssl`
   - `pdo_mysql`

### Step 5: Final Setup

```bash
# Clear all caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set final permissions
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

### Step 6: Access Your Application

1. Visit: `https://your-domain.com`
2. The installer should start automatically
3. **License validation is completely bypassed** - no purchase code needed
4. Follow the web installer to complete setup

## ✅ What's Fixed

- ❌ **No more purchase code validation**
- ❌ **No more "Invalid purchase code format" errors**
- ❌ **No more license verification**
- ✅ **Symlink requirement removed**
- ✅ **Complete license bypass implemented**

## 🔧 Key Files Modified

- `LicenseVerificationRequest.php` - Validation rules disabled
- `InstallController.php` - License methods bypassed
- `config/installer.php` - License requirement disabled
- `.env.example` - Added bypass environment variables

## 🚨 Troubleshooting

If you still get errors:

```bash
# Check database connection
php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database OK';"

# Check PHP extensions
php -m | grep -E "(tokenizer|fileinfo|xml|dom)"

# Regenerate everything
php artisan key:generate --force
php artisan config:clear
php artisan cache:clear
```

Your application should now install completely without any license restrictions!