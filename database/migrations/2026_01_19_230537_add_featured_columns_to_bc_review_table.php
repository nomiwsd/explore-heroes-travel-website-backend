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
            if (!Schema::hasColumn('bc_review', 'vendor_id')) {
                $table->unsignedBigInteger('vendor_id')->nullable()->after('status');
            }
            if (!Schema::hasColumn('bc_review', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('status');
            }
            if (!Schema::hasColumn('bc_review', 'show_on_homepage')) {
                $table->boolean('show_on_homepage')->default(false)->after('status');
            }
            if (!Schema::hasColumn('bc_review', 'show_on_tour_page')) {
                $table->boolean('show_on_tour_page')->default(false)->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bc_review', function (Blueprint $table) {
            $table->dropColumn(['vendor_id', 'is_featured', 'show_on_homepage', 'show_on_tour_page']);
        });
    }
};
