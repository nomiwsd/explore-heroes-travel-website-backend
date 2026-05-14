<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extend the per-locale SEO table (bc_seo) so admins can save translated
     * OG / Twitter / keywords values alongside seo_title / seo_desc. Without
     * these columns the translated SEO form silently dropped admin edits.
     */
    public function up(): void
    {
        if (!Schema::hasTable('bc_seo')) {
            return;
        }

        Schema::table('bc_seo', function (Blueprint $table) {
            if (!Schema::hasColumn('bc_seo', 'seo_keywords')) {
                $table->text('seo_keywords')->nullable()->after('seo_desc');
            }
            if (!Schema::hasColumn('bc_seo', 'og_title')) {
                $table->string('og_title', 255)->nullable()->after('seo_keywords');
            }
            if (!Schema::hasColumn('bc_seo', 'og_description')) {
                $table->text('og_description')->nullable()->after('og_title');
            }
            if (!Schema::hasColumn('bc_seo', 'twitter_title')) {
                $table->string('twitter_title', 255)->nullable()->after('og_description');
            }
            if (!Schema::hasColumn('bc_seo', 'twitter_description')) {
                $table->text('twitter_description')->nullable()->after('twitter_title');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('bc_seo')) {
            return;
        }
        Schema::table('bc_seo', function (Blueprint $table) {
            foreach (['seo_keywords', 'og_title', 'og_description', 'twitter_title', 'twitter_description'] as $col) {
                if (Schema::hasColumn('bc_seo', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
