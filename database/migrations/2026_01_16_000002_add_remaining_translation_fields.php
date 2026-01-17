<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds all required columns to translation tables
     */
    public function up(): void
    {
        // ========================================
        // TOUR TRANSLATIONS - bc_tour_translations
        // Translatable fields: title, short_desc, address, faqs, itinerary, 
        // surrounding, inclusions, exclusions, conditions, cancellation_policy,
        // child_policy, payment_terms
        // ========================================
        Schema::table('bc_tour_translations', function (Blueprint $table) {
            if (!Schema::hasColumn('bc_tour_translations', 'inclusions')) {
                $table->json('inclusions')->nullable();
            }
            if (!Schema::hasColumn('bc_tour_translations', 'exclusions')) {
                $table->json('exclusions')->nullable();
            }
            if (!Schema::hasColumn('bc_tour_translations', 'conditions')) {
                $table->text('conditions')->nullable();
            }
            if (!Schema::hasColumn('bc_tour_translations', 'cancellation_policy')) {
                $table->text('cancellation_policy')->nullable();
            }
            if (!Schema::hasColumn('bc_tour_translations', 'child_policy')) {
                $table->text('child_policy')->nullable();
            }
            if (!Schema::hasColumn('bc_tour_translations', 'payment_terms')) {
                $table->text('payment_terms')->nullable();
            }
        });

        // ========================================
        // NEWS TRANSLATIONS - core_news_translations  
        // Translatable fields: title, content, short_desc, excerpt
        // ========================================
        Schema::table('core_news_translations', function (Blueprint $table) {
            if (!Schema::hasColumn('core_news_translations', 'short_desc')) {
                $table->text('short_desc')->nullable();
            }
            if (!Schema::hasColumn('core_news_translations', 'excerpt')) {
                $table->text('excerpt')->nullable();
            }
        });

        // ========================================
        // PAGE TRANSLATIONS - core_page_translations
        // Translatable fields: title, content, short_desc, banner_title
        // ========================================
        Schema::table('core_page_translations', function (Blueprint $table) {
            if (!Schema::hasColumn('core_page_translations', 'short_desc')) {
                $table->text('short_desc')->nullable();
            }
            if (!Schema::hasColumn('core_page_translations', 'banner_title')) {
                $table->string('banner_title', 255)->nullable();
            }
        });

        // ========================================
        // LOCATION TRANSLATIONS - bc_location_translations
        // Translatable fields: name, content, short_description, trip_ideas
        // Already has all required fields - no changes needed
        // ========================================
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bc_tour_translations', function (Blueprint $table) {
            $columns = ['inclusions', 'exclusions', 'conditions', 'cancellation_policy', 'child_policy', 'payment_terms'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('bc_tour_translations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('core_news_translations', function (Blueprint $table) {
            $columns = ['short_desc', 'excerpt'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('core_news_translations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('core_page_translations', function (Blueprint $table) {
            $columns = ['short_desc', 'banner_title'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('core_page_translations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
