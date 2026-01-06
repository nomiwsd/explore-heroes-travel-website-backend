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
            if (!Schema::hasColumn('bc_contact', 'notes')) {
                $table->text('notes')->nullable()->after('special_requirements');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bc_contact', function (Blueprint $table) {
            if (Schema::hasColumn('bc_contact', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
