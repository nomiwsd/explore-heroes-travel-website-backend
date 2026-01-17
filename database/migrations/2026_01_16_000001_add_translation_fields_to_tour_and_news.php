<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds missing translation fields to tour, news, location and page translation tables
     */
    public function up(): void
    {
        // ========================================
        // TOUR TRANSLATIONS - bc_tour_translations
        // ========================================
        Schema::table('bc_tour_translations', function (Blueprint $table) {
            // Policy fields
            if (!Schema::hasColumn('bc_tour_translations', 'conditions')) {
                $table->text('conditions')->nullable()->after('surrounding');
            }
            if (!Schema::hasColumn('bc_tour_translations', 'cancellation_policy')) {
                $table->text('cancellation_policy')->nullable()->after('conditions');
            }
            if (!Schema::hasColumn('bc_tour_translations', 'child_policy')) {
                $table->text('child_policy')->nullable()->after('cancellation_policy');
            }
            if (!Schema::hasColumn('bc_tour_translations', 'payment_terms')) {
                $table->text('payment_terms')->nullable()->after('child_policy');
            }
            // New array field names (in addition to include/exclude)
            if (!Schema::hasColumn('bc_tour_translations', 'inclusions')) {
                $table->json('inclusions')->nullable()->after('itinerary');
            }
            if (!Schema::hasColumn('bc_tour_translations', 'exclusions')) {
                $table->json('exclusions')->nullable()->after('inclusions');
            }
        });

        // ========================================
        // NEWS TRANSLATIONS - core_news_translations
        // ========================================
        Schema::table('core_news_translations', function (Blueprint $table) {
            if (!Schema::hasColumn('core_news_translations', 'excerpt')) {
                $table->text('excerpt')->nullable()->after('content');
            }
            if (!Schema::hasColumn('core_news_translations', 'short_desc')) {
                $table->text('short_desc')->nullable()->after('excerpt');
            }
        });

        // ========================================
        // PAGE TRANSLATIONS - core_page_translations
        // ========================================
        Schema::table('core_page_translations', function (Blueprint $table) {
            if (!Schema::hasColumn('core_page_translations', 'short_desc')) {
                $table->text('short_desc')->nullable()->after('content');
            }
        });

        // LocationTranslation already has all required fields:
        // name, content, short_description, trip_ideas
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bc_tour_translations', function (Blueprint $table) {
            $columns = ['conditions', 'cancellation_policy', 'child_policy', 'payment_terms', 'inclusions', 'exclusions'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('bc_tour_translations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('core_news_translations', function (Blueprint $table) {
            $columns = ['excerpt', 'short_desc'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('core_news_translations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('core_page_translations', function (Blueprint $table) {
            if (Schema::hasColumn('core_page_translations', 'short_desc')) {
                $table->dropColumn('short_desc');
            }
        });
    }
};
