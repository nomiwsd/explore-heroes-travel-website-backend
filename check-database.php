<?php
/**
 * Database Diagnostic Script
 * Run this to check if all required tables and data exist
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=================================================\n";
echo "DATABASE DIAGNOSTIC CHECK\n";
echo "=================================================\n\n";

// Check database connection
echo "1. Testing Database Connection...\n";
try {
    DB::connection()->getPdo();
    echo "   ✅ Database connected successfully\n\n";
} catch (\Exception $e) {
    echo "   ❌ Database connection failed: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Check if permissions table exists
echo "2. Checking Permissions Table...\n";
if (Schema::hasTable('permissions')) {
    $count = DB::table('permissions')->count();
    echo "   ✅ permissions table exists\n";
    echo "   📊 Permissions count: $count\n";
    if ($count === 0) {
        echo "   ⚠️  WARNING: No permissions found! Run migrations.\n";
    }
} else {
    echo "   ❌ permissions table MISSING!\n";
    echo "   🔧 FIX: Run 'php artisan migrate --force'\n";
}
echo "\n";

// Check if roles table exists
echo "3. Checking Roles Table...\n";
if (Schema::hasTable('roles')) {
    $count = DB::table('roles')->count();
    echo "   ✅ roles table exists\n";
    echo "   📊 Roles count: $count\n";
    if ($count === 0) {
        echo "   ⚠️  WARNING: No roles found! Run migrations.\n";
    }
} else {
    echo "   ❌ roles table MISSING!\n";
    echo "   🔧 FIX: Run 'php artisan migrate --force'\n";
}
echo "\n";

// Check core_translations table
echo "4. Checking Translations Table...\n";
if (Schema::hasTable('core_translations')) {
    $rawCount = DB::table('core_translations')->where('locale', 'raw')->count();
    $enCount = DB::table('core_translations')->where('locale', 'en')->count();
    $arCount = DB::table('core_translations')->where('locale', 'ar')->count();
    echo "   ✅ core_translations table exists\n";
    echo "   📊 Raw strings: $rawCount\n";
    echo "   📊 EN translations: $enCount\n";
    echo "   📊 AR translations: $arCount\n";
    if ($rawCount === 0) {
        echo "   ⚠️  WARNING: No translation strings! Run migrations.\n";
    }
} else {
    echo "   ❌ core_translations table MISSING!\n";
}
echo "\n";

// Check core_languages table
echo "5. Checking Languages Table...\n";
if (Schema::hasTable('core_languages')) {
    $languages = DB::table('core_languages')->select('id', 'name', 'locale', 'status')->get();
    echo "   ✅ core_languages table exists\n";
    echo "   📊 Languages count: " . $languages->count() . "\n";
    foreach ($languages as $lang) {
        echo "      - {$lang->name} ({$lang->locale}) - {$lang->status}\n";
    }
    
    // Check if egy exists
    $egyExists = DB::table('core_languages')->where('locale', 'egy')->exists();
    if ($egyExists) {
        echo "   ✅ Egyptian (egy) language exists\n";
    } else {
        echo "   ℹ️  Egyptian (egy) language not found (this is OK)\n";
    }
} else {
    echo "   ❌ core_languages table MISSING!\n";
}
echo "\n";

// Check migrations table
echo "6. Checking Migrations Status...\n";
$latestMigrations = DB::table('migrations')
    ->orderBy('id', 'desc')
    ->limit(5)
    ->get(['migration', 'batch']);

echo "   📋 Last 5 migrations:\n";
foreach ($latestMigrations as $migration) {
    echo "      [{$migration->batch}] {$migration->migration}\n";
}

// Check if our new migrations are there
$permissionMigration = DB::table('migrations')
    ->where('migration', 'like', '%create_permission_tables%')
    ->exists();
$translationSeed = DB::table('migrations')
    ->where('migration', 'like', '%seed_translation_strings%')
    ->exists();

echo "\n   Our migrations status:\n";
if ($permissionMigration) {
    echo "   ✅ Permission tables migration: RAN\n";
} else {
    echo "   ❌ Permission tables migration: NOT RAN\n";
    echo "   🔧 FIX: Run 'php artisan migrate --force'\n";
}

if ($translationSeed) {
    echo "   ✅ Translation seed migration: RAN\n";
} else {
    echo "   ❌ Translation seed migration: NOT RAN\n";
    echo "   🔧 FIX: Run 'php artisan migrate --force'\n";
}

echo "\n";

// Summary
echo "=================================================\n";
echo "SUMMARY\n";
echo "=================================================\n";

$issues = [];

if (!Schema::hasTable('permissions')) {
    $issues[] = "Missing permissions table";
}
if (!Schema::hasTable('roles')) {
    $issues[] = "Missing roles table";
}
if (Schema::hasTable('core_translations') && DB::table('core_translations')->where('locale', 'raw')->count() === 0) {
    $issues[] = "No translation strings in database";
}

if (empty($issues)) {
    echo "✅ All checks passed! Database is ready.\n";
} else {
    echo "❌ Issues found:\n";
    foreach ($issues as $issue) {
        echo "   - $issue\n";
    }
    echo "\n🔧 TO FIX: Run these commands on production:\n";
    echo "   1. php artisan migrate --force\n";
    echo "   2. php artisan translations:build\n";
    echo "   3. php artisan route:clear\n";
    echo "   4. php artisan config:clear\n";
}

echo "=================================================\n";
