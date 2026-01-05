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
        Schema::table('core_news', function (Blueprint $table) {
            // Featured flag for showing on homepage/about page
            if (!Schema::hasColumn('core_news', 'is_featured')) {
                $table->tinyInteger('is_featured')->default(0)->after('status');
            }
            
            // OG Image for social sharing (separate from featured image)
            if (!Schema::hasColumn('core_news', 'og_image_id')) {
                $table->integer('og_image_id')->nullable()->after('image_id');
            }
            
            // Featured Image Alt Text
            if (!Schema::hasColumn('core_news', 'image_alt')) {
                $table->string('image_alt', 255)->nullable()->after('og_image_id');
            }
            
            // Related Posts (manual selection - stores JSON array of post IDs)
            if (!Schema::hasColumn('core_news', 'related_posts')) {
                $table->text('related_posts')->nullable()->after('gallery');
            }
            
            // Destination/Location relationship
            if (!Schema::hasColumn('core_news', 'location_id')) {
                $table->integer('location_id')->nullable()->after('cat_id');
            }
            
            // Author bio for display
            if (!Schema::hasColumn('core_news', 'author_bio')) {
                $table->text('author_bio')->nullable()->after('author_id');
            }
            
            // Excerpt/Short description
            if (!Schema::hasColumn('core_news', 'excerpt')) {
                $table->text('excerpt')->nullable()->after('content');
            }
            
            // Reading time
            if (!Schema::hasColumn('core_news', 'reading_time')) {
                $table->integer('reading_time')->nullable()->after('excerpt');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('core_news', function (Blueprint $table) {
            $columns = ['is_featured', 'og_image_id', 'image_alt', 'related_posts', 'location_id', 'author_bio', 'excerpt', 'reading_time'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('core_news', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
