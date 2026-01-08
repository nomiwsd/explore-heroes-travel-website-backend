<?php
/**
 * Remove All Images Script
 *
 * This script removes all images from the media_files table.
 * Run this script via command line: php remove-all-images.php
 *
 * WARNING: This will permanently delete all media files from the database!
 * Make sure to backup your database before running this script.
 */

// Set execution time and error reporting
set_time_limit(300);
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "=== Remove All Images Script ===\n";
echo "WARNING: This will delete ALL images from the database!\n\n";

$basePath = __DIR__;
echo "Base Path: $basePath\n\n";

// Include Laravel's autoloader
require_once $basePath . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once $basePath . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Connected to Database ===\n";

try {
    // Get database connection
    $db = DB::connection();

    echo "Database: " . $db->getDatabaseName() . "\n";
    echo "Driver: " . $db->getDriverName() . "\n\n";

    // Count current images
    $count = DB::table('media_files')->count();
    echo "Current images in database: $count\n\n";

    if ($count === 0) {
        echo "No images to remove.\n";
        exit(0);
    }

    // Ask for confirmation (in command line)
    if (php_sapi_name() === 'cli') {
        echo "Are you sure you want to delete ALL $count images? (type 'yes' to confirm): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        if (trim($line) !== 'yes') {
            echo "Operation cancelled.\n";
            exit(0);
        }
    }

    echo "=== Starting Image Removal ===\n";

    try {
        // Truncate media_files table (faster than delete and resets auto-increment)
        DB::statement('TRUNCATE TABLE media_files');

        echo "✓ Successfully truncated media_files table.\n";

        // Verify
        $remaining = DB::table('media_files')->count();
        echo "Remaining images: $remaining\n";

        if ($remaining === 0) {
            echo "✓ All images successfully removed!\n";
        } else {
            echo "⚠ Warning: $remaining images still remain in database.\n";
        }

    } catch (Exception $e) {
        // If truncate fails (due to foreign keys), try delete instead
        echo "Truncate failed, trying DELETE instead...\n";
        $deleted = DB::table('media_files')->delete();
        echo "✓ Successfully deleted $deleted images from database.\n";

        // Verify
        $remaining = DB::table('media_files')->count();
        echo "Remaining images: $remaining\n";
    }

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n=== Script Completed ===\n";
echo "Note: This script only removes database records. Physical files on disk are not affected.\n";
echo "If you need to clean up physical files, you may need to manually delete them from storage/app/public/ or other directories.\n";
?>