#!/bin/bash

echo "Fixing deployment errors..."

# Navigate to your application directory
cd /home/1548555.cloudwaysapps.com/hewbhsdagx/public_html

# Install missing Sanctum package
echo "Installing Laravel Sanctum..."
composer require laravel/sanctum

# Install missing PHP extensions
echo "Installing required PHP extensions..."

# For Ubuntu/Debian systems
if command -v apt &> /dev/null; then
    sudo apt update
    sudo apt install -y php-tokenizer php-fileinfo php-xml php-dom
fi

# For CentOS/RHEL systems (common on some hosting providers)
if command -v yum &> /dev/null; then
    sudo yum install -y php-tokenizer php-fileinfo php-xml php-dom
fi

# Clear all caches
echo "Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Publish Sanctum configuration
echo "Publishing Sanctum configuration..."
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# Run migrations for Sanctum
echo "Running Sanctum migrations..."
php artisan migrate

# Regenerate autoload files
echo "Regenerating autoload files..."
composer dump-autoload

# Set proper permissions
echo "Setting permissions..."
chmod -R 755 storage
chmod -R 755 bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Cache configurations for production
echo "Caching configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Fix completed!"