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
        Schema::table('bc_review', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->after('status');
            $table->boolean('show_on_homepage')->default(false)->after('is_featured');
            $table->boolean('show_on_tour_page')->default(false)->after('show_on_homepage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bc_review', function (Blueprint $table) {
            $table->dropColumn(['is_featured', 'show_on_homepage', 'show_on_tour_page']);
        });
    }
};
