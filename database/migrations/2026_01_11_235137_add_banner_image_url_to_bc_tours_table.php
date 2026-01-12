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
        Schema::table('bc_tours', function (Blueprint $table) {
            if (!Schema::hasColumn('bc_tours', 'banner_image_url')) {
                $table->string('banner_image_url')->nullable()->after('banner_image_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bc_tours', function (Blueprint $table) {
            if (Schema::hasColumn('bc_tours', 'banner_image_url')) {
                $table->dropColumn('banner_image_url');
            }
        });
    }
};
