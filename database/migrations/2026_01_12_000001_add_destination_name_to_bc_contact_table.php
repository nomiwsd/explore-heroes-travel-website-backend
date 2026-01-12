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
        Schema::table('bc_contact', function (Blueprint $table) {
            // Add destination_name field for storing selected destination
            if (!Schema::hasColumn('bc_contact', 'destination_name')) {
                $table->string('destination_name', 255)->nullable()->after('tour_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bc_contact', function (Blueprint $table) {
            if (Schema::hasColumn('bc_contact', 'destination_name')) {
                $table->dropColumn('destination_name');
            }
        });
    }
};
