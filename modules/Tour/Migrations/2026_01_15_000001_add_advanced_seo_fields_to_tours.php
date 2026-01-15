<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bc_tours', function (Blueprint $table) {
            if (!Schema::hasColumn('bc_tours', 'canonical_url')) {
                $table->string('canonical_url')->nullable();
            }
            if (!Schema::hasColumn('bc_tours', 'robots_meta')) {
                $table->string('robots_meta')->nullable();
            }
            if (!Schema::hasColumn('bc_tours', 'schema_markup')) {
                $table->text('schema_markup')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('bc_tours', function (Blueprint $table) {
            $table->dropColumn(['canonical_url', 'robots_meta', 'schema_markup']);
        });
    }
};
