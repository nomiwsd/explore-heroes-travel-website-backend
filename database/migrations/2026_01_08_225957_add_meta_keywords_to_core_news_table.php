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
        Schema::table('core_news', function (Blueprint $table) {
            $table->string('meta_keywords', 500)->nullable()->after('og_image_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('core_news', function (Blueprint $table) {
            $table->dropColumn('meta_keywords');
        });
    }
};
