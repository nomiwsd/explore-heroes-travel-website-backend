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
                // `special_requirements` is added by a later migration, so only
                // position the column when it already exists (fresh installs).
                $column = $table->text('notes')->nullable();
                if (Schema::hasColumn('bc_contact', 'special_requirements')) {
                    $column->after('special_requirements');
                }
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
