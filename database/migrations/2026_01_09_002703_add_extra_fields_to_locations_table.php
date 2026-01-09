<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddExtraFieldsToLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bc_locations', function (Blueprint $table) {
            if (!Schema::hasColumn('bc_locations', 'show_on_homepage')) {
                $table->tinyInteger('show_on_homepage')->nullable()->default(0);
            }
            if (!Schema::hasColumn('bc_locations', 'destination_type')) {
                $table->string('destination_type', 50)->nullable()->default('city');
            }
            if (!Schema::hasColumn('bc_locations', 'display_order')) {
                $table->integer('display_order')->nullable()->default(0);
            }
            if (!Schema::hasColumn('bc_locations', 'is_featured')) {
                $table->tinyInteger('is_featured')->nullable()->default(0);
            }
        });

        Schema::table('bc_location_translations', function (Blueprint $table) {
            if (!Schema::hasColumn('bc_location_translations', 'short_description')) {
                $table->text('short_description')->nullable();
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
        Schema::table('bc_locations', function (Blueprint $table) {
            $table->dropColumn(['show_on_homepage', 'destination_type', 'display_order', 'is_featured']);
        });

        Schema::table('bc_location_translations', function (Blueprint $table) {
            $table->dropColumn(['short_description']);
        });
    }
}
