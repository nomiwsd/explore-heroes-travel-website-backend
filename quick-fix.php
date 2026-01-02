<?php
/**
 * Quick Fix - Run this to fix all production issues
 */

echo "=================================================\n";
echo "QUICK FIX SCRIPT\n";
echo "=================================================\n\n";

$steps = [
    [
        'name' => 'Check if Spatie Permission package is installed',
        'check' => function() {
            return class_exists('Spatie\\Permission\\Models\\Permission');
        },
        'fix' => 'Run: composer update spatie/laravel-permission',
    ],
    [
        'name' => 'Check storage/app permissions',
        'check' => function() {
            return is_writable(storage_path('app'));
        },
        'fix' => 'Run: chmod -R 775 storage && chown -R www-data:www-data storage',
    ],
    [
        'name' => 'Check bootstrap/cache permissions',
        'check' => function() {
            return is_writable(base_path('bootstrap/cache'));
        },
        'fix' => 'Run: chmod -R 775 bootstrap/cache && chown -R www-data:www-data bootstrap/cache',
    ],
    [
        'name' => 'Check if config is cached',
        'check' => function() {
            return !file_exists(base_path('bootstrap/cache/config.php'));
        },
        'fix' => 'Run: php artisan config:clear',
    ],
    [
        'name' => 'Check if routes are cached',
        'check' => function() {
            return !file_exists(base_path('bootstrap/cache/routes-v7.php'));
        },
        'fix' => 'Run: php artisan route:clear',
    ],
];

$failed = [];
$fixes = [];

foreach ($steps as $i => $step) {
    $num = $i + 1;
    echo "$num. " . $step['name'] . "...\n";
    
    try {
        if ($step['check']()) {
            echo "   ✅ OK\n";
        } else {
            echo "   ❌ FAILED\n";
            $failed[] = $step['name'];
            $fixes[] = $step['fix'];
        }
    } catch (\Exception $e) {
        echo "   ❌ ERROR: " . $e->getMessage() . "\n";
        $failed[] = $step['name'];
        $fixes[] = $step['fix'];
    }
    echo "\n";
}

echo "=================================================\n";
echo "SUMMARY\n";
echo "=================================================\n\n";

if (empty($failed)) {
    echo "✅ All checks passed!\n\n";
    echo "If you're still getting 500 errors, the issue is elsewhere.\n";
    echo "Run 'php test-apis.php' to test specific endpoints.\n";
} else {
    echo "❌ Found " . count($failed) . " issue(s):\n\n";
    foreach ($failed as $i => $issue) {
        echo ($i + 1) . ". $issue\n";
    }
    
    echo "\n=================================================\n";
    echo "FIXES\n";
    echo "=================================================\n\n";
    echo "Run these commands in order:\n\n";
    foreach ($fixes as $i => $fix) {
        echo ($i + 1) . ". $fix\n";
    }
}

echo "\n=================================================\n";

// If everything is OK, try to load autoloader and test
if (empty($failed)) {
    echo "\nRunning quick API test...\n\n";
    
    require __DIR__.'/vendor/autoload.php';
    $app = require_once __DIR__.'/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    // Test permissions API
    try {
        $permissions = \Spatie\Permission\Models\Permission::count();
        echo "✅ Permissions API works - found $permissions permissions\n";
    } catch (\Exception $e) {
        echo "❌ Permissions API error: " . $e->getMessage() . "\n";
    }
    
    // Test translations
    try {
        $translations = DB::table('core_translations')
            ->where('locale', 'raw')
            ->count();
        echo "✅ Translations works - found $translations strings\n";
    } catch (\Exception $e) {
        echo "❌ Translations error: " . $e->getMessage() . "\n";
    }
}

echo "\n";
