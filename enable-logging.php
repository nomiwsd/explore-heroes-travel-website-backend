<?php
/**
 * Enable detailed error logging
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=================================================\n";
echo "ENABLE ERROR LOGGING\n";
echo "=================================================\n\n";

// Check .env file
$envFile = base_path('.env');
if (!file_exists($envFile)) {
    echo "❌ .env file not found!\n";
    echo "🔧 Copy .env.example to .env\n";
    exit(1);
}

$envContent = file_get_contents($envFile);

echo "Current settings:\n";
echo "  APP_DEBUG: " . config('app.debug') . "\n";
echo "  APP_ENV: " . config('app.env') . "\n";
echo "  LOG_CHANNEL: " . config('logging.default') . "\n";
echo "  LOG_LEVEL: " . config('logging.channels.' . config('logging.default') . '.level', 'not set') . "\n";
echo "\n";

// Check if we should enable debug
if (config('app.env') === 'production' && config('app.debug') === false) {
    echo "⚠️  WARNING: Debug is disabled in production\n";
    echo "For troubleshooting, you can temporarily enable it:\n";
    echo "1. Edit .env file: APP_DEBUG=true\n";
    echo "2. Run: php artisan config:clear\n";
    echo "3. Test your APIs\n";
    echo "4. Check logs at storage/logs/laravel.log\n";
    echo "5. IMPORTANT: Set APP_DEBUG=false when done!\n";
    echo "\n";
}

// Check log file
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $size = filesize($logFile);
    $readable = $size < 1024 ? $size . ' B' : ($size < 1048576 ? round($size/1024, 2) . ' KB' : round($size/1048576, 2) . ' MB');
    echo "✅ Log file exists: $readable\n";
    echo "   Location: $logFile\n";

    // Show last few lines
    $lines = file($logFile);
    if (count($lines) > 0) {
        echo "\n📋 Last 10 lines of log:\n";
        echo "   " . str_repeat("-", 70) . "\n";
        foreach (array_slice($lines, -10) as $line) {
            echo "   " . trim($line) . "\n";
        }
        echo "   " . str_repeat("-", 70) . "\n";
    }
} else {
    echo "ℹ️  No log file yet\n";
    echo "   Will be created on first error\n";
}

echo "\n";

// Check storage permissions
$logDir = storage_path('logs');
if (is_writable($logDir)) {
    echo "✅ logs directory is writable\n";
} else {
    echo "❌ logs directory is NOT writable\n";
    echo "🔧 FIX: chmod -R 775 $logDir\n";
    echo "🔧 FIX: chown -R www-data:www-data $logDir\n";
}

echo "\n";
echo "=================================================\n";
echo "QUICK TEST\n";
echo "=================================================\n\n";

// Try to write to log
try {
    \Illuminate\Support\Facades\Log::info('Test log entry from enable-logging.php script');
    echo "✅ Successfully wrote test entry to log\n";

    // Check if it was actually written
    if (file_exists($logFile)) {
        $lastLine = exec("tail -n 1 $logFile 2>&1");
        if (str_contains($lastLine, 'Test log entry')) {
            echo "✅ Verified: Test entry appears in log file\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ Failed to write to log: " . $e->getMessage() . "\n";
}

echo "\n=================================================\n";
