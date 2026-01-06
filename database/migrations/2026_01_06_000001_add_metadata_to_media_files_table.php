<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMetadataToMediaFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('media_files', function (Blueprint $table) {
            // Only add columns if they don't exist
            if (!Schema::hasColumn('media_files', 'alt_text')) {
                $table->string('alt_text', 500)->nullable()->after('file_height');
            }
            if (!Schema::hasColumn('media_files', 'title')) {
                $table->string('title', 255)->nullable()->after('alt_text');
            }
            if (!Schema::hasColumn('media_files', 'description')) {
                $table->text('description')->nullable()->after('title');
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
        Schema::table('media_files', function (Blueprint $table) {
            $table->dropColumn(['alt_text', 'title', 'description']);
        });
    }
}
