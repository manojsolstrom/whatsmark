# Cloudways Specific Deployment Guide

## 1. Install Required Packages via SSH

# Connect to your server via SSH (from Cloudways panel)
ssh master@server-ip -p port

# Navigate to your app directory
cd /home/1548555.cloudwaysapps.com/hewbhsdagx/public_html

# Install Laravel Sanctum
composer require laravel/sanctum

# If composer install fails, try:
composer install --no-dev --optimize-autoloader

## 2. Update composer.json (if needed)

Add these to your composer.json requirements:
"laravel/sanctum": "^3.3",
"php": "^8.1",

## 3. Environment Configuration

# Copy environment file
cp .env.example .env

# Edit .env file with Cloudways database credentials
# (Get these from Cloudways Application Management panel)

APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database settings (from Cloudways panel)
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_db_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

## 4. Run Laravel Commands

# Generate application key
php artisan key:generate

# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Publish Sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# Run migrations
php artisan migrate --force

# Create storage link
php artisan storage:link

# Cache for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

## 5. File Permissions (via Cloudways panel or SSH)

# Set proper permissions
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod -R 777 storage
chmod -R 777 bootstrap/cache

## 6. Cloudways Specific Settings

# In Cloudways panel:
# - Set PHP version to 8.1 or 8.2
# - Enable required PHP extensions:
#   - tokenizer
#   - fileinfo
#   - xml
#   - dom
#   - mbstring
#   - openssl
#   - pdo_mysql

## 7. Varnish Cache (if enabled)
# Purge Varnish cache from Cloudways panel after deployment