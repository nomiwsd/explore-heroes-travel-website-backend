<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add short_desc to core_news table
        if (!Schema::hasColumn('core_news', 'short_desc')) {
            Schema::table('core_news', function (Blueprint $table) {
                $table->text('short_desc')->nullable()->after('content');
            });
        }

        // Add excerpt as alias for short_desc (if needed)
        if (!Schema::hasColumn('core_news', 'excerpt')) {
            Schema::table('core_news', function (Blueprint $table) {
                $table->text('excerpt')->nullable()->after('content');
            });
        }

        // Add image_id to core_news_category table
        if (!Schema::hasColumn('core_news_category', 'image_id')) {
            Schema::table('core_news_category', function (Blueprint $table) {
                $table->integer('image_id')->nullable()->after('content');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('core_news', 'short_desc')) {
            Schema::table('core_news', function (Blueprint $table) {
                $table->dropColumn('short_desc');
            });
        }

        if (Schema::hasColumn('core_news', 'excerpt')) {
            Schema::table('core_news', function (Blueprint $table) {
                $table->dropColumn('excerpt');
            });
        }

        if (Schema::hasColumn('core_news_category', 'image_id')) {
            Schema::table('core_news_category', function (Blueprint $table) {
                $table->dropColumn('image_id');
            });
        }
    }
};
