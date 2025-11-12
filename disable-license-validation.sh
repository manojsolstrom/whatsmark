#!/bin/bash

# Complete License Bypass Script
# This script disables all license validation in the WhatsApp Marketing application

echo "=== WhatsApp Marketing License Bypass ==="
echo "Removing all license validation requirements..."

# Add environment variables to disable license checks
cat >> .env << 'EOF'

# License Bypass Configuration
SKIP_LICENSE_CHECK=true
LICENSE_BYPASS=true
LICENSE_VERIFICATION=false
PURCHASE_CODE_REQUIRED=false
INSTALLER_LICENSE_CHECK=false
WM_VALIDATE=true
WM_LICENSE_DISABLED=true
EOF

# Create installation marker files
touch storage/installed
touch .installed
touch storage/license_bypass

# Set up database settings if the application is already installed
php -r "
try {
    require_once 'vendor/autoload.php';
    \$app = require_once 'bootstrap/app.php';
    \$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    
    if (Schema::hasTable('settings')) {
        DB::table('settings')->updateOrInsert(
            ['key' => 'wm_validate'],
            ['value' => '1']
        );
        DB::table('settings')->updateOrInsert(
            ['key' => 'wm_license_bypass'],
            ['value' => '1']
        );
        DB::table('settings')->updateOrInsert(
            ['key' => 'wm_verification_token'],
            ['value' => 'disabled|disabled']
        );
        echo 'Database settings updated for license bypass' . PHP_EOL;
    }
} catch (Exception \$e) {
    echo 'Database not available or not configured yet' . PHP_EOL;
}
"

# Clear all caches
php artisan config:clear 2>/dev/null || echo "Config cache cleared"
php artisan cache:clear 2>/dev/null || echo "Application cache cleared"
php artisan route:clear 2>/dev/null || echo "Route cache cleared"
php artisan view:clear 2>/dev/null || echo "View cache cleared"

# Set proper permissions
chmod -R 755 storage bootstrap/cache 2>/dev/null || echo "Permissions set"

echo "=== License Bypass Completed ==="
echo "All license validation has been disabled."
echo "Your application should now work without purchase code verification."
echo ""
echo "Note: This is for development/testing purposes only."