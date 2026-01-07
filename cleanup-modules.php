<?php

/**
 * Module Cleanup Script
 * 
 * This script removes unused modules from the backend.
 * Run this script from the command line: php cleanup-modules.php
 * 
 * MODULES TO REMOVE:
 * - Boat
 * - Booking
 * - Car
 * - Coupon
 * - Event
 * - Flight
 * - Hotel
 * - Space
 * - Vendor
 * 
 * MODULES TO KEEP:
 * - Tour (used by frontend)
 * - Location (used by frontend)
 * - News (used by frontend)
 * - Page (used by frontend)
 * - Review (used by frontend)
 * - Contact (used by frontend)
 * - Media (used by frontend)
 * - Language (used by frontend)
 * - User (used by frontend)
 * - Core (used by frontend)
 * - Sms (as requested)
 * - Report (as requested)
 * - Api (helper module)
 */

$modulesToRemove = [
    'Boat',
    'Booking', 
    'Car',
    'Coupon',
    'Event',
    'Flight',
    'Hotel',
    'Space',
    'Vendor',
];

$modulesPath = __DIR__ . '/modules';

echo "==============================================\n";
echo "EXPLORE HEROES - MODULE CLEANUP SCRIPT\n";
echo "==============================================\n\n";

// Check if running in dry-run mode
$dryRun = in_array('--dry-run', $argv ?? []);
if ($dryRun) {
    echo "Running in DRY-RUN mode (no files will be deleted)\n\n";
}

foreach ($modulesToRemove as $module) {
    $modulePath = $modulesPath . '/' . $module;
    
    if (is_dir($modulePath)) {
        echo "Found module to remove: {$module}\n";
        
        if (!$dryRun) {
            if (deleteDirectory($modulePath)) {
                echo "  ✓ Deleted: {$module}\n";
            } else {
                echo "  ✗ Failed to delete: {$module}\n";
            }
        } else {
            echo "  [DRY-RUN] Would delete: {$modulePath}\n";
        }
    } else {
        echo "Module not found (already removed?): {$module}\n";
    }
}

echo "\n==============================================\n";
echo "CLEANUP COMPLETE\n";
echo "==============================================\n";

echo "\nRemaining modules:\n";
$remainingModules = array_filter(scandir($modulesPath), function($item) use ($modulesPath) {
    return is_dir($modulesPath . '/' . $item) && !in_array($item, ['.', '..']);
});

foreach ($remainingModules as $module) {
    echo "  - {$module}\n";
}

/**
 * Recursively delete a directory
 */
function deleteDirectory($dir) {
    if (!is_dir($dir)) {
        return false;
    }
    
    $files = array_diff(scandir($dir), ['.', '..']);
    
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            deleteDirectory($path);
        } else {
            unlink($path);
        }
    }
    
    return rmdir($dir);
}
