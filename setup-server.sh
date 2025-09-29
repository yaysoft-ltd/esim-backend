#!/bin/bash

# EsimTel DigitalOcean Server Setup Script
# Ubuntu 22.04 LTS için optimized

set -e

echo "🚀 EsimTel Server Setup Starting..."
echo "📋 This will install: Nginx, PHP 8.2, MySQL, Redis, Node.js, Composer"

# System update
echo "📦 Updating system packages..."
apt update && apt upgrade -y

# Install required packages
echo "🔧 Installing system dependencies..."
apt install -y software-properties-common curl wget git unzip supervisor

# Add PHP repository
add-apt-repository ppa:ondrej/php -y
apt update

# Install Nginx
echo "🌐 Installing Nginx..."
apt install -y nginx

# Install PHP 8.2 and extensions
echo "🐘 Installing PHP 8.2 and extensions..."
apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-xml php8.2-curl \
    php8.2-mbstring php8.2-zip php8.2-gd php8.2-intl php8.2-bcmath \
    php8.2-redis php8.2-cli php8.2-common php8.2-opcache

# Install MySQL
echo "🗄️ Installing MySQL..."
apt install -y mysql-server

# Install Redis
echo "📡 Installing Redis..."
apt install -y redis-server

# Install Node.js (for frontend assets)
echo "📦 Installing Node.js..."
curl -fsSL https://deb.nodesource.com/setup_18.x | bash -
apt install -y nodejs

# Install Composer
echo "🎼 Installing Composer..."
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# Create project directory
echo "📁 Creating project directory..."
mkdir -p /var/www/esimtel
cd /var/www/esimtel

# Set permissions
chown -R www-data:www-data /var/www/esimtel
chmod -R 755 /var/www/esimtel

# Configure Nginx
echo "🔧 Configuring Nginx..."
cat > /etc/nginx/sites-available/esimtel << 'EOL'
server {
    listen 80;
    server_name _;
    root /var/www/esimtel/public_html/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    client_max_body_size 20M;
}
EOL

# Enable site
ln -sf /etc/nginx/sites-available/esimtel /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Test nginx config
nginx -t

# Configure PHP
echo "🐘 Configuring PHP..."
sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 20M/' /etc/php/8.2/fpm/php.ini
sed -i 's/post_max_size = 8M/post_max_size = 25M/' /etc/php/8.2/fpm/php.ini
sed -i 's/max_execution_time = 30/max_execution_time = 300/' /etc/php/8.2/fpm/php.ini
sed -i 's/memory_limit = 128M/memory_limit = 256M/' /etc/php/8.2/fpm/php.ini

# Enable OPCache
sed -i 's/;opcache.enable=1/opcache.enable=1/' /etc/php/8.2/fpm/php.ini
sed -i 's/;opcache.memory_consumption=128/opcache.memory_consumption=128/' /etc/php/8.2/fpm/php.ini
sed -i 's/;opcache.max_accelerated_files=10000/opcache.max_accelerated_files=10000/' /etc/php/8.2/fpm/php.ini

# Configure MySQL
echo "🗄️ Configuring MySQL..."
mysql_secure_installation

echo ""
echo "🔐 Please create database and user for EsimTel:"
echo "mysql -u root -p"
echo "CREATE DATABASE esimtel_production;"
echo "CREATE USER 'esimtel_user'@'localhost' IDENTIFIED BY 'your_secure_password';"
echo "GRANT ALL PRIVILEGES ON esimtel_production.* TO 'esimtel_user'@'localhost';"
echo "FLUSH PRIVILEGES;"
echo "EXIT;"
echo ""

# Configure Supervisor for Laravel Queues
echo "👷 Configuring Supervisor..."
cat > /etc/supervisor/conf.d/laravel-worker.conf << 'EOL'
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/esimtel/current/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/esimtel/storage/logs/worker.log
stopwaitsecs=3600
EOL

# Start and enable services
echo "🚀 Starting services..."
systemctl enable nginx
systemctl enable php8.2-fpm
systemctl enable mysql
systemctl enable redis-server
systemctl enable supervisor

systemctl start nginx
systemctl start php8.2-fpm
systemctl start mysql
systemctl start redis-server
systemctl start supervisor

# Reload supervisor
supervisorctl reread
supervisorctl update

# Configure firewall
echo "🔥 Configuring firewall..."
ufw --force enable
ufw allow 22
ufw allow 80
ufw allow 443

# Create swap file (1GB)
echo "💾 Creating swap file..."
fallocate -l 1G /swapfile
chmod 600 /swapfile
mkswap /swapfile
swapon /swapfile
echo '/swapfile none swap sw 0 0' | tee -a /etc/fstab

echo ""
echo "✅ Server setup completed!"
echo ""
echo "📋 Next steps:"
echo "1. Create MySQL database and user (commands shown above)"
echo "2. Configure GitHub Secrets with server details"
echo "3. Push your code to trigger deployment"
echo ""
echo "🌐 Server IP: $(curl -s ifconfig.me)"
echo "📊 Server specs: $(free -h | grep Mem | awk '{print $2}') RAM, $(df -h | grep '/$' | awk '{print $2}') Disk"
echo ""
echo "🔧 Useful commands:"
echo "  - Check services: systemctl status nginx php8.2-fpm mysql redis supervisor"
echo "  - Nginx logs: tail -f /var/log/nginx/error.log"
echo "  - Laravel logs: tail -f /var/www/esimtel/current/storage/logs/laravel.log"
echo "  - Queue workers: supervisorctl status"
echo ""