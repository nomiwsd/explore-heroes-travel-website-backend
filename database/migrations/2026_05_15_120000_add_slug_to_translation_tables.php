<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a per-locale `slug` column to the four translation tables so admins
     * can have a separate URL slug per language (e.g. /en/tours/sri-lanka-tour
     * vs /ar/tours/جولة-سريلانكا).
     */
    public function up(): void
    {
        $tables = [
            'bc_tour_translations',
            'core_news_translations',
            'core_page_translations',
            'bc_location_translations',
        ];

        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'slug')) {
                    // Nullable so existing rows are valid; not unique because
                    // the same translation slug can legally exist across
                    // different origin rows in different locales.
                    $table->string('slug', 255)->nullable()->after('locale');
                    // Index name is truncated to <64 chars (MySQL limit).
                    $indexName = substr($tableName, 0, 45) . '_loc_slug_idx';
                    $table->index(['locale', 'slug'], $indexName);
                }
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'bc_tour_translations',
            'core_news_translations',
            'core_page_translations',
            'bc_location_translations',
        ];

        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'slug')) {
                    $indexName = substr($tableName, 0, 45) . '_loc_slug_idx';
                    $table->dropIndex($indexName);
                    $table->dropColumn('slug');
                }
            });
        }
    }
};
