<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('core_pages', function (Blueprint $table) {
            if (!Schema::hasColumn('core_pages', 'meta_title')) {
                $table->string('meta_title', 255)->nullable()->after('banner_title');
            }
            if (!Schema::hasColumn('core_pages', 'meta_desc')) {
                $table->text('meta_desc')->nullable()->after('meta_title');
            }
            if (!Schema::hasColumn('core_pages', 'meta_keywords')) {
                $table->string('meta_keywords', 500)->nullable()->after('meta_desc');
            }
            // Add extra page fields that might be missing
            if (!Schema::hasColumn('core_pages', 'header_style')) {
                $table->string('header_style', 50)->nullable()->after('meta_keywords');
            }
            if (!Schema::hasColumn('core_pages', 'custom_logo')) {
                $table->string('custom_logo', 255)->nullable()->after('header_style');
            }
            if (!Schema::hasColumn('core_pages', 'banner_title')) {
                $table->string('banner_title', 255)->nullable();
            }
            if (!Schema::hasColumn('core_pages', 'banner_image_id')) {
                $table->integer('banner_image_id')->nullable();
            }
            if (!Schema::hasColumn('core_pages', 'display_order')) {
                $table->integer('display_order')->default(0);
            }
            if (!Schema::hasColumn('core_pages', 'show_in_menu')) {
                $table->boolean('show_in_menu')->default(false);
            }
            if (!Schema::hasColumn('core_pages', 'show_in_header')) {
                $table->boolean('show_in_header')->default(false);
            }
            if (!Schema::hasColumn('core_pages', 'show_in_footer')) {
                $table->boolean('show_in_footer')->default(false);
            }
            if (!Schema::hasColumn('core_pages', 'is_homepage')) {
                $table->boolean('is_homepage')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('core_pages', function (Blueprint $table) {
            $columns = ['meta_title', 'meta_desc', 'meta_keywords'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('core_pages', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
