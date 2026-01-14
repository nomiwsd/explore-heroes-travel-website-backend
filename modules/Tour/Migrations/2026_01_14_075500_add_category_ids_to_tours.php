<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCategoryIdsToTours extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bc_tours', function (Blueprint $table) {
            // Add category_ids column for multi-category support
            if (!Schema::hasColumn('bc_tours', 'category_ids')) {
                $table->json('category_ids')->nullable()->after('short_desc');
            }
            
            // Drop unused columns
            if (Schema::hasColumn('bc_tours', 'category_id')) {
                $table->dropColumn('category_id');
            }
            if (Schema::hasColumn('bc_tours', 'video')) {
                $table->dropColumn('video');
            }
            if (Schema::hasColumn('bc_tours', 'content')) {
                $table->dropColumn('content');
            }
            if (Schema::hasColumn('bc_tours', 'summary_inclusions')) {
                $table->dropColumn('summary_inclusions');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bc_tours', function (Blueprint $table) {
            // Restore dropped columns
            if (!Schema::hasColumn('bc_tours', 'category_id')) {
                $table->unsignedBigInteger('category_id')->nullable();
            }
            if (!Schema::hasColumn('bc_tours', 'video')) {
                $table->string('video')->nullable();
            }
            if (!Schema::hasColumn('bc_tours', 'content')) {
                $table->longText('content')->nullable();
            }
            if (!Schema::hasColumn('bc_tours', 'summary_inclusions')) {
                $table->json('summary_inclusions')->nullable();
            }
            
            // Drop category_ids
            if (Schema::hasColumn('bc_tours', 'category_ids')) {
                $table->dropColumn('category_ids');
            }
        });
    }
}
