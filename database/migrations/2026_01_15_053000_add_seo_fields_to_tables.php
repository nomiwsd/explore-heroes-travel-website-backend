<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSeoFieldsToTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Pages
        Schema::table('core_pages', function (Blueprint $table) {
            if (!Schema::hasColumn('core_pages', 'og_title')) $table->string('og_title', 255)->nullable();
            if (!Schema::hasColumn('core_pages', 'og_description')) $table->text('og_description')->nullable();
            if (!Schema::hasColumn('core_pages', 'og_image_id')) $table->integer('og_image_id')->nullable();
            
            if (!Schema::hasColumn('core_pages', 'twitter_card')) $table->string('twitter_card', 50)->nullable();
            if (!Schema::hasColumn('core_pages', 'twitter_title')) $table->string('twitter_title', 255)->nullable();
            if (!Schema::hasColumn('core_pages', 'twitter_description')) $table->text('twitter_description')->nullable();
            if (!Schema::hasColumn('core_pages', 'twitter_image_id')) $table->integer('twitter_image_id')->nullable();
            
            if (!Schema::hasColumn('core_pages', 'canonical_url')) $table->string('canonical_url', 255)->nullable();
            if (!Schema::hasColumn('core_pages', 'robots_meta')) $table->string('robots_meta', 50)->nullable();
            if (!Schema::hasColumn('core_pages', 'schema_markup')) $table->text('schema_markup')->nullable();
        });

        // 2. Tours
        Schema::table('bc_tours', function (Blueprint $table) {
            // Tours generally have other fields, but we add missing ones
            if (!Schema::hasColumn('bc_tours', 'canonical_url')) $table->string('canonical_url', 255)->nullable();
            if (!Schema::hasColumn('bc_tours', 'robots_meta')) $table->string('robots_meta', 50)->nullable();
            if (!Schema::hasColumn('bc_tours', 'schema_markup')) $table->text('schema_markup')->nullable();
        });

        // 3. Locations (Destinations)
        Schema::table('bc_locations', function (Blueprint $table) {
            if (!Schema::hasColumn('bc_locations', 'seo_title')) $table->string('seo_title', 255)->nullable();
            if (!Schema::hasColumn('bc_locations', 'seo_desc')) $table->text('seo_desc')->nullable();
            if (!Schema::hasColumn('bc_locations', 'seo_image')) $table->string('seo_image', 255)->nullable();
            if (!Schema::hasColumn('bc_locations', 'seo_share')) $table->text('seo_share')->nullable();

            if (!Schema::hasColumn('bc_locations', 'og_title')) $table->string('og_title', 255)->nullable();
            if (!Schema::hasColumn('bc_locations', 'og_description')) $table->text('og_description')->nullable();
            if (!Schema::hasColumn('bc_locations', 'og_image_id')) $table->integer('og_image_id')->nullable();
            
            if (!Schema::hasColumn('bc_locations', 'twitter_card')) $table->string('twitter_card', 50)->nullable();
            if (!Schema::hasColumn('bc_locations', 'twitter_title')) $table->string('twitter_title', 255)->nullable();
            if (!Schema::hasColumn('bc_locations', 'twitter_description')) $table->text('twitter_description')->nullable();
            if (!Schema::hasColumn('bc_locations', 'twitter_image_id')) $table->integer('twitter_image_id')->nullable();
            
            if (!Schema::hasColumn('bc_locations', 'canonical_url')) $table->string('canonical_url', 255)->nullable();
            if (!Schema::hasColumn('bc_locations', 'robots_meta')) $table->string('robots_meta', 50)->nullable();
            if (!Schema::hasColumn('bc_locations', 'schema_markup')) $table->text('schema_markup')->nullable();
        });

        // 4. News (Blog)
        Schema::table('core_news', function (Blueprint $table) {
            if (!Schema::hasColumn('core_news', 'og_title')) $table->string('og_title', 255)->nullable();
            if (!Schema::hasColumn('core_news', 'og_description')) $table->text('og_description')->nullable();
            
            if (!Schema::hasColumn('core_news', 'twitter_card')) $table->string('twitter_card', 50)->nullable();
            if (!Schema::hasColumn('core_news', 'twitter_title')) $table->string('twitter_title', 255)->nullable();
            if (!Schema::hasColumn('core_news', 'twitter_description')) $table->text('twitter_description')->nullable();
            if (!Schema::hasColumn('core_news', 'twitter_image_id')) $table->integer('twitter_image_id')->nullable();
            
            if (!Schema::hasColumn('core_news', 'canonical_url')) $table->string('canonical_url', 255)->nullable();
            if (!Schema::hasColumn('core_news', 'robots_meta')) $table->string('robots_meta', 50)->nullable();
            if (!Schema::hasColumn('core_news', 'schema_markup')) $table->text('schema_markup')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Dropping columns is complex in batch, so we skip detailed down logic to avoid data loss accidental risk
        // In production, each table would have its columns dropped.
    }
}
