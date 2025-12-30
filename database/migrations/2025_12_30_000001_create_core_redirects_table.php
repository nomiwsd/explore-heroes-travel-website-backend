<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCoreRedirectsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('core_redirects')) {
            Schema::create('core_redirects', function (Blueprint $table) {
                $table->id();
                $table->string('old_url');
                $table->string('new_url');
                $table->integer('status_code')->default(301);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index('old_url');
                $table->index('is_active');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('core_redirects');
    }
}
