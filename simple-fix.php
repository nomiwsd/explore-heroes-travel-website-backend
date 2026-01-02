<?php
/**
 * Simple Fix - No Laravel bootstrap needed
 */

echo "=================================================\n";
echo "SIMPLE FIX SCRIPT\n";
echo "=================================================\n\n";

echo "1. Checking if Spatie Permission package exists...\n";
$composerFile = __DIR__ . '/composer.json';
if (file_exists($composerFile)) {
    $composer = json_decode(file_get_contents($composerFile), true);
    if (isset($composer['require']['spatie/laravel-permission'])) {
        echo "   ✅ Package is in composer.json\n";
    } else {
        echo "   ❌ Package NOT in composer.json\n";
        echo "   🔧 FIX: Add '\"spatie/laravel-permission\": \"^6.0\"' to require section\n";
    }
} else {
    echo "   ❌ composer.json not found\n";
}

echo "\n2. Checking if vendor directory exists...\n";
if (is_dir(__DIR__ . '/vendor')) {
    echo "   ✅ vendor directory exists\n";
} else {
    echo "   ❌ vendor directory missing\n";
    echo "   🔧 FIX: Run 'composer install'\n";
}

echo "\n3. Checking storage permissions...\n";
$storageDir = __DIR__ . '/storage';
if (is_writable($storageDir)) {
    echo "   ✅ storage directory is writable\n";
} else {
    echo "   ❌ storage directory is NOT writable\n";
    echo "   🔧 FIX: chmod -R 775 storage && chown -R www-data:www-data storage\n";
}

echo "\n4. Checking bootstrap/cache permissions...\n";
$cacheDir = __DIR__ . '/bootstrap/cache';
if (is_writable($cacheDir)) {
    echo "   ✅ bootstrap/cache directory is writable\n";
} else {
    echo "   ❌ bootstrap/cache directory is NOT writable\n";
    echo "   🔧 FIX: chmod -R 775 bootstrap/cache && chown -R www-data:www-data bootstrap/cache\n";
}

echo "\n=================================================\n";
echo "RECOMMENDED FIXES\n";
echo "=================================================\n\n";

echo "Run these commands in order:\n\n";
echo "1. composer update spatie/laravel-permission --no-interaction\n";
echo "2. php artisan vendor:publish --provider=\"Spatie\\Permission\\PermissionServiceProvider\" --force\n";
echo "3. chmod -R 775 storage bootstrap/cache\n";
echo "4. chown -R www-data:www-data storage bootstrap/cache\n";
echo "5. php artisan config:clear\n";
echo "6. php artisan route:clear\n";
echo "7. php artisan cache:clear\n";
echo "8. php artisan view:clear\n\n";

echo "After running above commands, test with:\n";
echo "php full-diagnostic.php\n\n";

echo "=================================================\n";
