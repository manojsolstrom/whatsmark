#!/bin/bash

# DigitalOcean Deployment Script for WhatsApp Marketing Application
echo "Starting deployment setup..."

# Update system
apt update && apt upgrade -y

# Install Nginx
apt install nginx -y
systemctl start nginx
systemctl enable nginx

# Install PHP 8.2 and required extensions
apt install software-properties-common -y
add-apt-repository ppa:ondrej/php -y
apt update
apt install php8.2 php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath php8.2-intl php8.2-redis -y

# Install MySQL
apt install mysql-server -y
systemctl start mysql
systemctl enable mysql

# Install Redis
apt install redis-server -y
systemctl start redis
systemctl enable redis

# Install Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

# Install Node.js and NPM
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
apt install nodejs -y

# Install Supervisor for queue management
apt install supervisor -y

# Clone your repository
cd /var/www
git clone https://github.com/manojsolstrom/whatsmark.git
cd whatsmark

# Set permissions
chown -R www-data:www-data /var/www/whatsmark
chmod -R 755 /var/www/whatsmark
chmod -R 775 /var/www/whatsmark/storage
chmod -R 775 /var/www/whatsmark/bootstrap/cache

# Install Composer dependencies
composer install --optimize-autoloader --no-dev

# Install NPM dependencies and build assets
npm install
npm run build

echo "Basic setup completed. Now configure database and environment..."