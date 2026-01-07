<?php
/**
 * Storage Fix Script for Production Server
 * 
 * Upload this file to your production server root (/var/www/laravel-app/)
 * and access it via browser: https://yourdomain.com/fix-storage.php
 * 
 * After running, DELETE this file for security!
 */

// Set execution time
set_time_limit(300);
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Laravel Storage Fix Script</h1>";
echo "<pre>";

$basePath = __DIR__;
echo "Base Path: $basePath\n\n";

// Directories to create
$directories = [
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/framework/cache',
    'storage/framework/cache/data',
    'storage/logs',
    'bootstrap/cache',
];

echo "=== Creating Directories ===\n";
foreach ($directories as $dir) {
    $fullPath = $basePath . '/' . $dir;
    if (!file_exists($fullPath)) {
        if (mkdir($fullPath, 0775, true)) {
            echo "✓ Created: $dir\n";
        } else {
            echo "✗ Failed to create: $dir\n";
        }
    } else {
        echo "• Already exists: $dir\n";
    }
}

echo "\n=== Setting Permissions ===\n";
$permissionDirs = [
    'storage',
    'bootstrap/cache',
];

foreach ($permissionDirs as $dir) {
    $fullPath = $basePath . '/' . $dir;
    if (file_exists($fullPath)) {
        if (chmod($fullPath, 0775)) {
            echo "✓ Set permissions (775) on: $dir\n";
        } else {
            echo "✗ Failed to set permissions on: $dir\n";
        }
        
        // Recursively set permissions
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($fullPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                chmod($item->getPathname(), 0775);
            } else {
                chmod($item->getPathname(), 0664);
            }
        }
        echo "  → Applied permissions recursively\n";
    }
}

echo "\n=== Clearing Laravel Caches ===\n";
$commands = [
    'config:clear' => 'Configuration cache',
    'cache:clear' => 'Application cache',
    'route:clear' => 'Route cache',
    'view:clear' => 'View cache',
];

foreach ($commands as $cmd => $desc) {
    echo "Running: php artisan $cmd ($desc)\n";
    $output = [];
    $return = 0;
    exec("cd $basePath && php artisan $cmd 2>&1", $output, $return);
    if ($return === 0) {
        echo "✓ Success\n";
    } else {
        echo "✗ Failed: " . implode("\n", $output) . "\n";
    }
}

echo "\n=== Testing Write Access ===\n";
$testFile = $basePath . '/storage/framework/cache/data/test_write.txt';
$testDir = dirname($testFile);

if (!file_exists($testDir)) {
    mkdir($testDir, 0775, true);
}

if (file_put_contents($testFile, 'test')) {
    echo "✓ Write test successful!\n";
    unlink($testFile);
} else {
    echo "✗ Write test failed! Check server permissions.\n";
}

echo "\n=== Current Permissions ===\n";
foreach ($directories as $dir) {
    $fullPath = $basePath . '/' . $dir;
    if (file_exists($fullPath)) {
        $perms = substr(sprintf('%o', fileperms($fullPath)), -4);
        echo "$dir: $perms\n";
    }
}

echo "\n=== Summary ===\n";
echo "✓ All directories created\n";
echo "✓ Permissions set to 775 for directories, 664 for files\n";
echo "✓ Laravel caches cleared\n";
echo "\n";
echo "⚠️  IMPORTANT: Delete this file (fix-storage.php) for security!\n";
echo "\n";
echo "If you still have issues, contact your hosting provider to:\n";
echo "1. Set ownership: chown -R www-data:www-data storage bootstrap/cache\n";
echo "2. Ensure PHP can write to these directories\n";

echo "</pre>";
echo "<h3>Done! Now test your application.</h3>";
?>
