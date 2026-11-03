<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * BL-04: per-locale featured image alt text + author bio for blog posts.
     * Mirrors core_news.image_alt / core_news.author_bio. Additive only.
     */
    public function up(): void
    {
        if (!Schema::hasTable('core_news_translations')) {
            return;
        }
        Schema::table('core_news_translations', function (Blueprint $table) {
            if (!Schema::hasColumn('core_news_translations', 'image_alt')) {
                $table->string('image_alt', 500)->nullable();
            }
            if (!Schema::hasColumn('core_news_translations', 'author_bio')) {
                $table->text('author_bio')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Intentionally a no-op: dropping these columns would delete the
        // translated alt texts and author bios entered on production.
    }
};
