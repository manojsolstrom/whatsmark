#!/bin/bash

# WhatsApp Marketing Application - Fresh Installation Script
# Run this script on your server for a complete clean installation

echo "========================================="
echo "WhatsApp Marketing - Fresh Installation"
echo "========================================="

# Navigate to application directory
cd /home/1548555.cloudwaysapps.com/hewbhsdagx/public_html

# Step 1: Clean previous installation
echo "Step 1: Cleaning previous installation..."
rm -rf .env
rm -rf storage/installed
rm -rf storage/.installed
rm -rf .installed

# Step 2: Pull latest code from GitHub
echo "Step 2: Pulling latest code..."
git pull origin main

# Step 3: Install/Update Composer dependencies
echo "Step 3: Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader

# Step 4: Set up environment file
echo "Step 4: Setting up environment..."
cp .env.example .env

# Add license bypass to environment
echo "" >> .env
echo "# License Bypass - Added during installation" >> .env
echo "SKIP_LICENSE_CHECK=true" >> .env
echo "LICENSE_BYPASS=true" >> .env
echo "LICENSE_VERIFICATION=false" >> .env
echo "PURCHASE_CODE_REQUIRED=false" >> .env

# Step 5: Generate application key
echo "Step 5: Generating application key..."
php artisan key:generate --force

# Step 6: Clear all caches
echo "Step 6: Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Step 7: Create necessary directories
echo "Step 7: Creating directories..."
mkdir -p storage/app/public
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions  
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p public/storage

# Step 8: Set proper permissions
echo "Step 8: Setting permissions..."
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod -R 777 storage
chmod -R 777 bootstrap/cache
chmod -R 755 public/storage

# Step 9: Create storage link (manual since symlink is disabled)
echo "Step 9: Creating storage link..."
rm -rf public/storage
mkdir -p public/storage
# Copy any existing files
cp -r storage/app/public/* public/storage/ 2>/dev/null || echo "No existing files to copy"

# Step 10: Install Node.js dependencies and build assets
echo "Step 10: Building frontend assets..."
npm install
npm run build

echo "========================================="
echo "Installation preparation completed!"
echo "========================================="
echo ""
echo "Next steps:"
echo "1. Configure your database credentials in .env file"
echo "2. Run: php artisan migrate --force"
echo "3. Access your application through the web installer"
echo "4. The license verification is now bypassed"
echo ""
echo "Your application should now install without license issues!"
echo "========================================="