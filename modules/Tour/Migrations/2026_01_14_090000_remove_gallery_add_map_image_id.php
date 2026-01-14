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
            if (Schema::hasColumn('bc_tours', 'gallery')) {
                $table->dropColumn('gallery');
            }
            if (!Schema::hasColumn('bc_tours', 'map_image_id')) {
                $table->integer('map_image_id')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bc_tours', function (Blueprint $table) {
            if (!Schema::hasColumn('bc_tours', 'gallery')) {
                $table->string('gallery', 255)->nullable();
            }
            if (Schema::hasColumn('bc_tours', 'map_image_id')) {
                $table->dropColumn('map_image_id');
            }
        });
    }
};
