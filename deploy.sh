#!/bin/bash

# EsimTel Deployment Script for DigitalOcean
# This script handles the server-side deployment process

set -e

PROJECT_DIR="/var/www/esimtel"
GITHUB_SHA=$1

echo "🚀 Starting deployment for commit: $GITHUB_SHA"

cd $PROJECT_DIR

# Create releases directory if it doesn't exist
mkdir -p releases

# Download and extract the new release
echo "📦 Downloading release artifact..."
RELEASE_DIR="releases/$GITHUB_SHA"
mkdir -p $RELEASE_DIR

# Extract the uploaded artifact
tar -xzf "$GITHUB_SHA.tar.gz" -C "$RELEASE_DIR"

echo "🔧 Setting up the new release..."

# Copy environment file
cp .env "$RELEASE_DIR/.env"

# Copy Firebase credentials if exists
if [ -f "storage/app/google/firebase.json" ]; then
    mkdir -p "$RELEASE_DIR/storage/app/google"
    cp storage/app/google/firebase.json "$RELEASE_DIR/storage/app/google/"
fi

# Set permissions
chown -R www-data:www-data "$RELEASE_DIR"
chmod -R 755 "$RELEASE_DIR"
chmod -R 775 "$RELEASE_DIR/storage" "$RELEASE_DIR/bootstrap/cache"

# Install dependencies and optimize
cd "$RELEASE_DIR"
composer install --optimize-autoloader --no-dev --no-interaction

# Laravel optimizations
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

# Update storage link
php artisan storage:link

echo "🔄 Switching to new release..."

# Switch to new release (zero-downtime deployment)
cd $PROJECT_DIR
ln -nfs "$RELEASE_DIR" current

# Create/update web server symlink
ln -nfs current public_html

# Restart background services
echo "🔄 Restarting services..."
supervisorctl restart laravel-worker:* || true
service nginx reload || service apache2 reload || true
service php8.2-fpm reload || true

# Cleanup old releases (keep last 5)
echo "🧹 Cleaning up old releases..."
cd releases
ls -t | tail -n +6 | xargs rm -rf

# Remove old artifacts
find $PROJECT_DIR -name "*.tar.gz" -mtime +7 -delete

echo "✅ Deployment completed successfully!"
echo "🌐 Application is live at: $(cat current/.env | grep APP_URL | cut -d '=' -f2)"