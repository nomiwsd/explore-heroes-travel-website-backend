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
        Schema::table('core_pages', function (Blueprint $table) {
            if (!Schema::hasColumn('core_pages', 'banner_title')) {
                $table->string('banner_title')->nullable();
            }
            if (!Schema::hasColumn('core_pages', 'display_order')) {
                $table->integer('display_order')->default(0);
            }
            if (!Schema::hasColumn('core_pages', 'show_in_menu')) {
                $table->boolean('show_in_menu')->default(false);
            }
            if (!Schema::hasColumn('core_pages', 'banner_image_id')) {
                $table->integer('banner_image_id')->nullable();
            }
            // custom_logo already exists in model (likely in table)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('core_pages', function (Blueprint $table) {
            if (Schema::hasColumn('core_pages', 'banner_title')) $table->dropColumn('banner_title');
            if (Schema::hasColumn('core_pages', 'display_order')) $table->dropColumn('display_order');
            if (Schema::hasColumn('core_pages', 'show_in_menu')) $table->dropColumn('show_in_menu');
            if (Schema::hasColumn('core_pages', 'banner_image_id')) $table->dropColumn('banner_image_id');
        });
    }
};
