<?php
/**
 * Clean Broken Images Script
 *
 * This script identifies and removes broken image records from the media_files table.
 * Broken images include:
 * - Files that don't exist on disk
 * - Files with zero bytes size
 * - Files with size mismatch between database and disk
 *
 * Run this script via command line: php clean-broken-images.php
 */

// Set execution time and error reporting
set_time_limit(600); // Longer timeout for file operations
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "=== Clean Broken Images Script ===\n";
echo "This script will identify and optionally remove broken image records.\n\n";

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

    // Get storage path
    $storagePath = storage_path('app/public');
    echo "Storage Path: $storagePath\n\n";

    // Get all media files
    $mediaFiles = DB::table('media_files')->get();
    $totalFiles = $mediaFiles->count();

    echo "Total media files in database: $totalFiles\n\n";

    if ($totalFiles === 0) {
        echo "No media files to check.\n";
        exit(0);
    }

    $brokenFiles = [];
    $missingFiles = [];
    $zeroByteFiles = [];
    $sizeMismatchFiles = [];

    echo "=== Scanning Files ===\n";

    foreach ($mediaFiles as $file) {
        $filePath = $file->file_path;
        $dbFileSize = $file->file_size;
        $fileName = $file->file_name;
        $id = $file->id;

        // Check if file path is absolute or relative
        if (strpos($filePath, '/') === 0 || strpos($filePath, '\\') === 0) {
            // Absolute path
            $fullPath = $filePath;
        } else {
            // Relative path - assume it's in storage/app/public
            $fullPath = $storagePath . '/' . $filePath;
        }

        echo "Checking ID $id: $fileName\n";
        echo "  DB Path: $filePath\n";
        echo "  Full Path: $fullPath\n";

        // Check if file exists
        if (!file_exists($fullPath)) {
            echo "  ✗ MISSING: File does not exist on disk\n";
            $missingFiles[] = [
                'id' => $id,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'reason' => 'file_missing'
            ];
            $brokenFiles[] = $id;
            continue;
        }

        // Check file size
        $actualSize = filesize($fullPath);
        echo "  DB Size: " . ($dbFileSize ?? 'null') . " bytes\n";
        echo "  Actual Size: $actualSize bytes\n";

        if ($actualSize === 0) {
            echo "  ✗ ZERO BYTES: File is empty\n";
            $zeroByteFiles[] = [
                'id' => $id,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'reason' => 'zero_bytes'
            ];
            $brokenFiles[] = $id;
        } elseif ($dbFileSize && $actualSize != $dbFileSize) {
            echo "  ✗ SIZE MISMATCH: Database size ($dbFileSize) != actual size ($actualSize)\n";
            $sizeMismatchFiles[] = [
                'id' => $id,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'db_size' => $dbFileSize,
                'actual_size' => $actualSize,
                'reason' => 'size_mismatch'
            ];
            $brokenFiles[] = $id;
        } else {
            echo "  ✓ OK\n";
        }

        echo "\n";
    }

    // Summary
    echo "=== Scan Results ===\n";
    echo "Total files scanned: $totalFiles\n";
    echo "Missing files: " . count($missingFiles) . "\n";
    echo "Zero byte files: " . count($zeroByteFiles) . "\n";
    echo "Size mismatch files: " . count($sizeMismatchFiles) . "\n";
    echo "Total broken files: " . count($brokenFiles) . "\n\n";

    if (count($brokenFiles) === 0) {
        echo "✓ No broken files found!\n";
        exit(0);
    }

    // Ask for confirmation
    if (php_sapi_name() === 'cli') {
        echo "Broken files found: " . count($brokenFiles) . "\n";
        echo "Do you want to remove these broken records from the database? (type 'yes' to confirm): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        if (trim($line) !== 'yes') {
            echo "Operation cancelled.\n";
            echo "\nBroken files list:\n";
            foreach ($missingFiles as $file) {
                echo "- ID {$file['id']}: {$file['file_name']} ({$file['reason']})\n";
            }
            foreach ($zeroByteFiles as $file) {
                echo "- ID {$file['id']}: {$file['file_name']} ({$file['reason']})\n";
            }
            foreach ($sizeMismatchFiles as $file) {
                echo "- ID {$file['id']}: {$file['file_name']} ({$file['reason']})\n";
            }
            exit(0);
        }
    }

    echo "=== Removing Broken Records ===\n";

    // Remove broken records
    $deleted = DB::table('media_files')->whereIn('id', $brokenFiles)->delete();

    echo "✓ Successfully deleted $deleted broken records from database.\n";

    // Verify
    $remaining = DB::table('media_files')->count();
    echo "Remaining images: $remaining\n";

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n=== Script Completed ===\n";
echo "Note: Physical files on disk were not deleted, only database records.\n";
?>