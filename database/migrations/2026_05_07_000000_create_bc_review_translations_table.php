<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bc_review_translations')) {
            Schema::create('bc_review_translations', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('origin_id')->nullable();
                $table->string('locale', 10)->nullable();

                $table->string('title', 255)->nullable();
                $table->text('content')->nullable();
                $table->text('trip_summary')->nullable();
                $table->string('agent_role', 255)->nullable();

                $table->bigInteger('create_user')->nullable();
                $table->bigInteger('update_user')->nullable();

                $table->unique(['origin_id', 'locale']);
                $table->timestamps();
            });
        } else {
            // Idempotent: add any columns missing on a partially-migrated env
            Schema::table('bc_review_translations', function (Blueprint $table) {
                if (!Schema::hasColumn('bc_review_translations', 'origin_id'))   $table->bigInteger('origin_id')->nullable();
                if (!Schema::hasColumn('bc_review_translations', 'locale'))      $table->string('locale', 10)->nullable();
                if (!Schema::hasColumn('bc_review_translations', 'title'))       $table->string('title', 255)->nullable();
                if (!Schema::hasColumn('bc_review_translations', 'content'))     $table->text('content')->nullable();
                if (!Schema::hasColumn('bc_review_translations', 'trip_summary')) $table->text('trip_summary')->nullable();
                if (!Schema::hasColumn('bc_review_translations', 'agent_role'))  $table->string('agent_role', 255)->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bc_review_translations');
    }
};
