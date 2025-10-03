#!/bin/bash

# eSIM Backend Database Setup Script
# This script sets up the database for production deployment

set -e  # Exit on error

echo "======================================"
echo "eSIM Backend Database Setup"
echo "======================================"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if .env exists
if [ ! -f .env ]; then
    echo -e "${RED}Error: .env file not found!${NC}"
    echo "Please create .env file with database credentials"
    exit 1
fi

# Source the .env file to get database credentials
export $(grep -v '^#' .env | xargs)

echo -e "${YELLOW}Database Configuration:${NC}"
echo "Host: $DB_HOST"
echo "Database: $DB_DATABASE"
echo "Username: $DB_USERNAME"
echo ""

# Test MySQL connection
echo -e "${YELLOW}Testing MySQL connection...${NC}"
if ! mysql -h"$DB_HOST" -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "SELECT 1;" &> /dev/null; then
    echo -e "${RED}Error: Cannot connect to MySQL!${NC}"
    echo "Please check your database credentials in .env file"
    exit 1
fi
echo -e "${GREEN}✓ MySQL connection successful${NC}"
echo ""

# Create database if it doesn't exist
echo -e "${YELLOW}Checking if database exists...${NC}"
if ! mysql -h"$DB_HOST" -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "USE $DB_DATABASE;" &> /dev/null; then
    echo -e "${YELLOW}Database does not exist. Creating...${NC}"
    mysql -h"$DB_HOST" -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS $DB_DATABASE CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    echo -e "${GREEN}✓ Database created${NC}"
else
    echo -e "${GREEN}✓ Database already exists${NC}"
fi
echo ""

# Ask user what to do
echo -e "${YELLOW}Choose setup option:${NC}"
echo "1) Fresh installation (WARNING: This will delete all existing data!)"
echo "2) Run migrations only (Keep existing data, add new tables/columns)"
echo "3) Run migrations and seeders (Add missing data without deleting)"
echo ""
read -p "Enter your choice (1-3): " choice

case $choice in
    1)
        echo -e "${RED}WARNING: This will delete ALL existing data!${NC}"
        read -p "Are you sure? Type 'yes' to continue: " confirm
        if [ "$confirm" != "yes" ]; then
            echo "Aborted."
            exit 0
        fi

        echo -e "${YELLOW}Running fresh migration...${NC}"
        php artisan migrate:fresh --force
        echo -e "${GREEN}✓ Fresh migration completed${NC}"
        echo ""

        echo -e "${YELLOW}Seeding database...${NC}"
        php artisan db:seed --force
        echo -e "${GREEN}✓ Database seeded${NC}"
        ;;
    2)
        echo -e "${YELLOW}Running migrations...${NC}"
        php artisan migrate --force
        echo -e "${GREEN}✓ Migrations completed${NC}"
        ;;
    3)
        echo -e "${YELLOW}Running migrations...${NC}"
        php artisan migrate --force
        echo -e "${GREEN}✓ Migrations completed${NC}"
        echo ""

        echo -e "${YELLOW}Seeding database...${NC}"
        php artisan db:seed --force
        echo -e "${GREEN}✓ Database seeded${NC}"
        ;;
    *)
        echo -e "${RED}Invalid choice${NC}"
        exit 1
        ;;
esac

echo ""
echo -e "${YELLOW}Additional setup commands...${NC}"

# Generate app key if not set
if [ "$APP_KEY" = "base64:GENERATE_NEW_KEY_ON_SERVER" ] || [ -z "$APP_KEY" ]; then
    echo -e "${YELLOW}Generating application key...${NC}"
    php artisan key:generate --force
    echo -e "${GREEN}✓ Application key generated${NC}"
else
    echo -e "${GREEN}✓ Application key already set${NC}"
fi

# Create storage link
echo -e "${YELLOW}Creating storage link...${NC}"
php artisan storage:link
echo -e "${GREEN}✓ Storage link created${NC}"

# Clear and cache config
echo -e "${YELLOW}Clearing and caching configuration...${NC}"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo -e "${GREEN}✓ Cache cleared${NC}"

# Optimize for production
echo -e "${YELLOW}Optimizing for production...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo -e "${GREEN}✓ Optimized for production${NC}"

echo ""
echo -e "${GREEN}======================================"
echo "Database setup completed successfully!"
echo "======================================${NC}"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo "1. Create admin user: php artisan tinker"
echo "   Then run: App\Models\User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => bcrypt('password'), 'role' => 'admin']);"
echo ""
echo "2. Check your application: Visit your domain"
echo ""
