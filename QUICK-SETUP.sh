# Quick Server Setup Commands

# Run this single command block on your server for instant setup:

cd /home/1548555.cloudwaysapps.com/hewbhsdagx/public_html && \
rm -rf * .* 2>/dev/null || true && \
git clone https://github.com/manojsolstrom/whatsmark.git . && \
composer install --no-dev --optimize-autoloader && \
cp .env.example .env && \
echo "SKIP_LICENSE_CHECK=true" >> .env && \
echo "LICENSE_BYPASS=true" >> .env && \
php artisan key:generate --force && \
php artisan config:clear && \
chmod -R 777 storage && \
chmod -R 777 bootstrap/cache && \
mkdir -p public/storage && \
echo "Setup completed! Now configure database in .env and run: php artisan migrate --force"