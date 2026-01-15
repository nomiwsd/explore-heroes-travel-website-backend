<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bc_tours', function (Blueprint $table) {
            if (!Schema::hasColumn('bc_tours', 'seo_keywords')) {
                $table->string('seo_keywords')->nullable();
            }
            if (!Schema::hasColumn('bc_tours', 'og_image_id')) {
                $table->bigInteger('og_image_id')->nullable();
            }
            if (!Schema::hasColumn('bc_tours', 'twitter_image_id')) {
                $table->bigInteger('twitter_image_id')->nullable();
            }
             if (!Schema::hasColumn('bc_tours', 'og_image_url')) {
                $table->string('og_image_url')->nullable();
            }
            if (!Schema::hasColumn('bc_tours', 'twitter_image_url')) {
                $table->string('twitter_image_url')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('bc_tours', function (Blueprint $table) {
            $table->dropColumn(['seo_keywords', 'og_image_id', 'twitter_image_id', 'og_image_url', 'twitter_image_url']);
        });
    }
};
