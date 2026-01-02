<?php
/**
 * Test actual API endpoints with HTTP requests
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=================================================\n";
echo "API ENDPOINT TESTING\n";
echo "=================================================\n\n";

// Test endpoints
$endpoints = [
    // Public APIs
    ['GET', '/api/translations/languages', 'Public translations languages'],
    ['GET', '/api/translations/en', 'Public EN translations'],
    
    // Admin APIs (these need auth but we can check if they return proper error)
    ['GET', '/admin/module/user/api/permissions', 'Admin permissions API'],
    ['POST', '/admin/module/language/translations/en/build', 'Admin build translations'],
    ['GET', '/admin/module/user/api/users', 'Admin users list'],
    ['GET', '/admin/module/user/api/roles', 'Admin roles list'],
];

echo "Testing endpoints...\n\n";

foreach ($endpoints as $endpoint) {
    list($method, $uri, $description) = $endpoint;
    
    echo "Testing: $description\n";
    echo "  $method $uri\n";
    
    try {
        $request = \Illuminate\Http\Request::create($uri, $method);
        
        // Simulate request
        $response = $app->handle($request);
        $statusCode = $response->getStatusCode();
        
        // Check CORS headers
        $corsHeaders = [];
        if ($response->headers->has('Access-Control-Allow-Origin')) {
            $corsHeaders[] = 'Access-Control-Allow-Origin: ' . $response->headers->get('Access-Control-Allow-Origin');
        }
        if ($response->headers->has('Access-Control-Allow-Methods')) {
            $corsHeaders[] = 'Access-Control-Allow-Methods: ' . $response->headers->get('Access-Control-Allow-Methods');
        }
        
        if ($statusCode == 200) {
            echo "  ✅ Status: $statusCode OK\n";
        } elseif ($statusCode == 401 || $statusCode == 403) {
            echo "  ⚠️  Status: $statusCode (Auth required - expected for admin APIs)\n";
        } else {
            echo "  ❌ Status: $statusCode ERROR\n";
            // Get response content for errors
            $content = $response->getContent();
            if (strlen($content) < 500) {
                echo "  📄 Response: " . substr($content, 0, 200) . "\n";
            }
        }
        
        if (!empty($corsHeaders)) {
            echo "  🌐 CORS: " . implode(', ', $corsHeaders) . "\n";
        } else {
            echo "  ❌ CORS: No CORS headers found!\n";
        }
        
    } catch (\Exception $e) {
        echo "  ❌ Exception: " . $e->getMessage() . "\n";
        echo "     File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
    
    echo "\n";
}

echo "=================================================\n";
echo "CORS CONFIGURATION CHECK\n";
echo "=================================================\n\n";

// Check CORS config
$corsConfig = config('cors');

echo "CORS Configuration:\n";
echo "  Paths: " . json_encode($corsConfig['paths'] ?? []) . "\n";
echo "  Allowed Origins: " . json_encode($corsConfig['allowed_origins'] ?? []) . "\n";
echo "  Allowed Methods: " . json_encode($corsConfig['allowed_methods'] ?? []) . "\n";
echo "  Allowed Headers: " . json_encode($corsConfig['allowed_headers'] ?? []) . "\n";
echo "  Exposed Headers: " . json_encode($corsConfig['exposed_headers'] ?? []) . "\n";
echo "  Max Age: " . ($corsConfig['max_age'] ?? 'not set') . "\n";
echo "  Supports Credentials: " . ($corsConfig['supports_credentials'] ?? false ? 'true' : 'false') . "\n";
echo "\n";

// Check if HandleCors middleware is registered
echo "Checking CORS Middleware...\n";
$middleware = config('app.middleware') ?? [];
$kernelMiddleware = [];

// Check kernel middlewares
$kernelClass = new ReflectionClass(\App\Http\Kernel::class);
$middlewareProperty = $kernelClass->getProperty('middleware');
$middlewareProperty->setAccessible(true);
$kernelInstance = app(\Illuminate\Contracts\Http\Kernel::class);
$kernelMiddleware = $middlewareProperty->getValue($kernelInstance);

$corsFound = false;
foreach ($kernelMiddleware as $mw) {
    if (str_contains($mw, 'HandleCors') || str_contains($mw, 'Cors')) {
        echo "  ✅ CORS middleware found: $mw\n";
        $corsFound = true;
    }
}

if (!$corsFound) {
    echo "  ❌ CORS middleware NOT found in global middleware!\n";
    echo "  🔧 FIX: Add \\Illuminate\\Http\\Middleware\\HandleCors::class to app/Http/Kernel.php\n";
}

echo "\n";
echo "=================================================\n";
echo "ERROR LOG ANALYSIS\n";
echo "=================================================\n\n";

// Check for recent errors in log
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $lines = file($logFile);
    $errorCount = 0;
    $recentErrors = [];
    
    // Get last 100 lines
    $recentLines = array_slice($lines, -100);
    
    foreach ($recentLines as $line) {
        if (str_contains($line, '[ERROR]') || str_contains($line, 'Exception')) {
            $errorCount++;
            if (count($recentErrors) < 5) {
                $recentErrors[] = trim($line);
            }
        }
    }
    
    echo "  📊 Found $errorCount errors in last 100 log lines\n\n";
    
    if (!empty($recentErrors)) {
        echo "  Recent errors:\n";
        foreach ($recentErrors as $error) {
            echo "  🔴 " . substr($error, 0, 150) . "...\n";
        }
    }
} else {
    echo "  ℹ️  No log file found - errors not being logged\n";
    echo "  🔧 Check storage/logs/ permissions\n";
}

echo "\n";
echo "=================================================\n";
echo "RECOMMENDED FIXES\n";
echo "=================================================\n";

$fixes = [];

// Check storage permissions
if (!is_writable(storage_path('app'))) {
    $fixes[] = "chmod -R 775 " . storage_path('app');
    $fixes[] = "chown -R www-data:www-data " . storage_path('app');
}

// Check CORS
if (!$corsFound) {
    $fixes[] = "Add HandleCors middleware to app/Http/Kernel.php \$middleware array";
}

// Always recommend these
$fixes[] = "php artisan config:clear";
$fixes[] = "php artisan route:clear";
$fixes[] = "php artisan cache:clear";

if (!empty($fixes)) {
    echo "\nRun these commands:\n";
    foreach ($fixes as $i => $fix) {
        echo ($i + 1) . ". $fix\n";
    }
}

echo "\n=================================================\n";
