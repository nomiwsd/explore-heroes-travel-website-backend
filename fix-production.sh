#!/bin/bash

echo "================================================="
echo "PRODUCTION FIX SCRIPT"
echo "================================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Step 1: Installing Spatie Permission Package${NC}"
composer update spatie/laravel-permission --no-interaction
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Package installed${NC}"
else
    echo -e "${RED}❌ Package installation failed${NC}"
    exit 1
fi
echo ""

echo -e "${YELLOW}Step 2: Publishing Permission Config${NC}"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --force
echo -e "${GREEN}✅ Config published${NC}"
echo ""

echo -e "${YELLOW}Step 3: Fixing Storage Permissions${NC}"
chmod -R 775 storage
chmod -R 775 bootstrap/cache
chown -R www-data:www-data storage
chown -R www-data:www-data bootstrap/cache
echo -e "${GREEN}✅ Permissions fixed${NC}"
echo ""

echo -e "${YELLOW}Step 4: Clearing All Caches${NC}"
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan view:clear
echo -e "${GREEN}✅ Caches cleared${NC}"
echo ""

echo -e "${YELLOW}Step 5: Running Diagnostics${NC}"
php full-diagnostic.php
echo ""

echo -e "${YELLOW}Step 6: Testing APIs${NC}"
php test-apis.php
echo ""

echo "================================================="
echo -e "${GREEN}PRODUCTION FIX COMPLETE!${NC}"
echo "================================================="
echo ""
echo "If you still see 500 errors, run:"
echo "  php enable-logging.php"
echo "  tail -f storage/logs/laravel.log"
echo ""
