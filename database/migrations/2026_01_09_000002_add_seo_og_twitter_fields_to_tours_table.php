<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add more missing fields for Tours module - SEO, OG, Twitter, pricing
     */
    public function up(): void
    {
        Schema::table('bc_tours', function (Blueprint $table) {
            // Duration & Pricing fields
            $table->string('duration_type', 50)->nullable()->default('days');
            $table->string('pricing_type', 50)->nullable()->default('per_person');
            $table->decimal('group_price', 12, 2)->nullable();
            $table->decimal('child_price', 12, 2)->nullable();

            // SEO fields
            $table->string('seo_title', 255)->nullable();
            $table->text('seo_desc')->nullable();
            $table->string('seo_image', 500)->nullable();
            $table->text('seo_share')->nullable();

            // OG (Open Graph) fields
            $table->string('og_title', 255)->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image', 500)->nullable();

            // Twitter Card fields
            $table->string('twitter_card', 50)->nullable()->default('summary_large_image');
            $table->string('twitter_title', 255)->nullable();
            $table->text('twitter_description')->nullable();
            $table->string('twitter_image', 500)->nullable();

            // Availability & Related
            $table->json('availability_dates')->nullable();
            $table->json('related_tour_ids')->nullable();

            // Alternative field names (some backends use these)
            $table->json('inclusions')->nullable();
            $table->json('exclusions')->nullable();
            $table->json('highlights')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bc_tours', function (Blueprint $table) {
            $table->dropColumn([
                'duration_type',
                'pricing_type',
                'group_price',
                'child_price',
                'seo_title',
                'seo_desc',
                'seo_image',
                'seo_share',
                'og_title',
                'og_description',
                'og_image',
                'twitter_card',
                'twitter_title',
                'twitter_description',
                'twitter_image',
                'availability_dates',
                'related_tour_ids',
                'inclusions',
                'exclusions',
                'highlights',
            ]);
        });
    }
};
