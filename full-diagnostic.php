<?php
/**
 * Full System Diagnostic - Checks everything
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=================================================\n";
echo "FULL SYSTEM DIAGNOSTIC\n";
echo "=================================================\n\n";

// 1. Check if controllers exist
echo "1. Checking Controllers...\n";
$controllers = [
    'RoleManagementController' => 'Modules\\User\\Admin\\RoleManagementController',
    'TranslationsController' => 'Modules\\Language\\Admin\\TranslationsController',
];

foreach ($controllers as $name => $class) {
    if (class_exists($class)) {
        echo "   ✅ $name exists\n";
        // Check if getPermissions method exists
        if ($name === 'RoleManagementController') {
            if (method_exists($class, 'getPermissions')) {
                echo "      ✅ getPermissions() method exists\n";
            } else {
                echo "      ❌ getPermissions() method MISSING\n";
            }
        }
        if ($name === 'TranslationsController') {
            if (method_exists($class, 'buildTranslationsApi')) {
                echo "      ✅ buildTranslationsApi() method exists\n";
            } else {
                echo "      ❌ buildTranslationsApi() method MISSING\n";
            }
        }
    } else {
        echo "   ❌ $name MISSING - Class: $class\n";
    }
}
echo "\n";

// 2. Check Permission model
echo "2. Checking Spatie Permission Package...\n";
if (class_exists('Spatie\\Permission\\Models\\Permission')) {
    echo "   ✅ Spatie Permission package loaded\n";
    try {
        $permCount = \Spatie\Permission\Models\Permission::count();
        echo "   ✅ Can query permissions: $permCount found\n";
    } catch (\Exception $e) {
        echo "   ❌ Error querying permissions: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ❌ Spatie Permission package NOT loaded\n";
}
echo "\n";

// 3. Check routes
echo "3. Checking Routes...\n";
try {
    $routes = \Illuminate\Support\Facades\Route::getRoutes();
    $adminRoutes = [];
    foreach ($routes as $route) {
        $uri = $route->uri();
        if (str_contains($uri, 'admin/module/user/api/permissions')) {
            $adminRoutes[] = $uri . ' [' . implode(',', $route->methods()) . ']';
        }
        if (str_contains($uri, 'admin/module/language/translations')) {
            $adminRoutes[] = $uri . ' [' . implode(',', $route->methods()) . ']';
        }
    }

    if (!empty($adminRoutes)) {
        echo "   ✅ Admin routes registered:\n";
        foreach (array_slice($adminRoutes, 0, 5) as $route) {
            echo "      - $route\n";
        }
    } else {
        echo "   ⚠️  No admin API routes found - might need route:clear\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Error loading routes: " . $e->getMessage() . "\n";
}
echo "\n";

// 4. Check writable directories
echo "4. Checking Directory Permissions...\n";
$dirs = [
    'storage/app' => storage_path('app'),
    'storage/logs' => storage_path('logs'),
    'storage/framework/cache' => storage_path('framework/cache'),
    'bootstrap/cache' => base_path('bootstrap/cache'),
];

foreach ($dirs as $name => $path) {
    if (is_writable($path)) {
        echo "   ✅ $name is writable\n";
    } else {
        echo "   ❌ $name is NOT writable - FIX: chmod -R 775 $path\n";
    }
}
echo "\n";

// 5. Check cache files
echo "5. Checking Cache Files...\n";
$cacheFiles = [
    'bootstrap/cache/config.php' => base_path('bootstrap/cache/config.php'),
    'bootstrap/cache/routes-v7.php' => base_path('bootstrap/cache/routes-v7.php'),
];

$hasCacheIssues = false;
foreach ($cacheFiles as $name => $path) {
    if (file_exists($path)) {
        echo "   ⚠️  CACHED: $name exists\n";
        $hasCacheIssues = true;
    } else {
        echo "   ✅ $name not cached\n";
    }
}

if ($hasCacheIssues) {
    echo "   🔧 FIX: Run these commands:\n";
    echo "      php artisan config:clear\n";
    echo "      php artisan route:clear\n";
    echo "      php artisan cache:clear\n";
}
echo "\n";

// 6. Check latest error log
echo "6. Checking Error Logs...\n";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $lines = file($logFile);
    $errorLines = array_slice(array_reverse($lines), 0, 20);

    echo "   📋 Last 20 lines from laravel.log:\n";
    echo "   " . str_repeat("-", 70) . "\n";
    foreach ($errorLines as $line) {
        if (str_contains($line, 'ERROR') || str_contains($line, 'Exception')) {
            echo "   🔴 " . trim($line) . "\n";
        }
    }
    echo "   " . str_repeat("-", 70) . "\n";
} else {
    echo "   ℹ️  No log file found at $logFile\n";
}
echo "\n";

// 7. Test actual API calls
echo "7. Testing API Endpoints...\n";
try {
    // Test permissions API
    echo "   Testing /admin/module/user/api/permissions...\n";
    $controller = new \Modules\User\Admin\RoleManagementController();
    $response = $controller->getPermissions();
    $data = $response->getData();
    echo "   ✅ Permissions API works - returned data\n";
} catch (\Exception $e) {
    echo "   ❌ Permissions API error: " . $e->getMessage() . "\n";
    echo "      File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

try {
    // Test translation build API
    echo "   Testing /admin/module/language/translations/en/build...\n";
    $controller = new \Modules\Language\Admin\TranslationsController();
    $request = new \Illuminate\Http\Request();
    $response = $controller->buildTranslationsApi($request, 'en');
    echo "   ✅ Translation build API works\n";
} catch (\Exception $e) {
    echo "   ❌ Translation build API error: " . $e->getMessage() . "\n";
    echo "      File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
echo "\n";

echo "=================================================\n";
echo "RECOMMENDED ACTIONS\n";
echo "=================================================\n";
echo "Run these commands on production:\n";
echo "1. composer dump-autoload\n";
echo "2. php artisan config:clear\n";
echo "3. php artisan route:clear\n";
echo "4. php artisan cache:clear\n";
echo "5. php artisan view:clear\n";
echo "6. chmod -R 775 storage bootstrap/cache\n";
echo "=================================================\n";
