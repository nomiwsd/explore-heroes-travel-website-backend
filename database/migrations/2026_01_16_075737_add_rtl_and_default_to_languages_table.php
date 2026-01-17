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
        Schema::table('core_languages', function (Blueprint $table) {
            if (!Schema::hasColumn('core_languages', 'is_rtl')) {
                $table->boolean('is_rtl')->nullable()->default(0);
            }
            if (!Schema::hasColumn('core_languages', 'is_default')) {
                $table->boolean('is_default')->nullable()->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('core_languages', function (Blueprint $table) {
            $table->dropColumn(['is_rtl', 'is_default']);
        });
    }
};
