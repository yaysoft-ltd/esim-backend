#!/bin/bash

# Digital Ocean Server Setup Script for Laravel Application
# Run this script on your Digital Ocean droplet

set -e

echo "Starting server setup for Laravel application..."

# Update system
echo "Updating system packages..."
sudo apt update && sudo apt upgrade -y

# Install required packages
echo "Installing required packages..."
sudo apt install -y nginx mysql-server php8.2 php8.2-fpm php8.2-mysql php8.2-mbstring \
    php8.2-xml php8.2-bcmath php8.2-curl php8.2-gd php8.2-zip php8.2-intl \
    unzip curl git supervisor redis-server nodejs npm

# Install Composer
echo "Installing Composer..."
cd /tmp
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Configure MySQL
echo "Configuring MySQL..."
sudo mysql_secure_installation

# Create database and user
echo "Creating database and user..."
sudo mysql -e "CREATE DATABASE IF NOT EXISTS esim_app;"
sudo mysql -e "CREATE USER IF NOT EXISTS 'esim_user'@'localhost' IDENTIFIED BY 'your_secure_password';"
sudo mysql -e "GRANT ALL PRIVILEGES ON esim_app.* TO 'esim_user'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

# Configure Nginx
echo "Configuring Nginx..."
sudo tee /etc/nginx/sites-available/esim-app > /dev/null <<EOL
server {
    listen 80;
    listen [::]:80;
    server_name your_domain.com www.your_domain.com;
    root /var/www/html/app/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOL

# Enable site and remove default
sudo ln -sf /etc/nginx/sites-available/esim-app /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default

# Test nginx configuration
sudo nginx -t

# Configure PHP-FPM
echo "Configuring PHP-FPM..."
sudo sed -i 's/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/' /etc/php/8.2/fpm/php.ini
sudo sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 20M/' /etc/php/8.2/fpm/php.ini
sudo sed -i 's/post_max_size = 8M/post_max_size = 20M/' /etc/php/8.2/fpm/php.ini
sudo sed -i 's/max_execution_time = 30/max_execution_time = 300/' /etc/php/8.2/fpm/php.ini
sudo sed -i 's/memory_limit = 128M/memory_limit = 512M/' /etc/php/8.2/fpm/php.ini

# Create application directory
echo "Creating application directory..."
sudo mkdir -p /var/www/html
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html

# Configure Supervisor for Laravel Queue
echo "Configuring Supervisor for Laravel Queue..."
sudo tee /etc/supervisor/conf.d/laravel-worker.conf > /dev/null <<EOL
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/app/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/laravel-worker.log
stopwaitsecs=3600
EOL

# Configure Redis
echo "Configuring Redis..."
sudo sed -i 's/# maxmemory <bytes>/maxmemory 256mb/' /etc/redis/redis.conf
sudo sed -i 's/# maxmemory-policy noeviction/maxmemory-policy allkeys-lru/' /etc/redis/redis.conf

# Start and enable services
echo "Starting and enabling services..."
sudo systemctl enable nginx
sudo systemctl enable php8.2-fpm
sudo systemctl enable mysql
sudo systemctl enable redis-server
sudo systemctl enable supervisor

sudo systemctl start nginx
sudo systemctl start php8.2-fpm
sudo systemctl start mysql
sudo systemctl start redis-server
sudo systemctl start supervisor

# Configure firewall
echo "Configuring firewall..."
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw --force enable

# Create .env template
echo "Creating .env template..."
sudo tee /var/www/html/.env.template > /dev/null <<EOL
APP_NAME="eSIM Application"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://your_domain.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=esim_app
DB_USERNAME=esim_user
DB_PASSWORD=your_secure_password

BROADCAST_DRIVER=redis
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="\${APP_NAME}"

# Add your API keys and other sensitive configurations here
AIRALO_CLIENT_ID=
AIRALO_CLIENT_SECRET=
RAZORPAY_KEY=
RAZORPAY_SECRET=
STRIPE_KEY=
STRIPE_SECRET=
CASHFREE_APP_ID=
CASHFREE_SECRET_KEY=
EOL

echo "Server setup completed!"
echo ""
echo "Next steps:"
echo "1. Update domain name in /etc/nginx/sites-available/esim-app"
echo "2. Copy /var/www/html/.env.template to /var/www/html/app/.env and configure"
echo "3. Install SSL certificate with Let's Encrypt"
echo "4. Configure GitHub secrets for deployment"
echo ""
echo "To install SSL certificate, run:"
echo "sudo apt install certbot python3-certbot-nginx"
echo "sudo certbot --nginx -d your_domain.com -d www.your_domain.com"