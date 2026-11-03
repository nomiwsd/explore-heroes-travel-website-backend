<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin > SEO > Redirects (Next.js admin, routes/api/admin/core.php) reads and
 * writes bc_redirects, but no migration created it, so fresh installs could not
 * store redirects. Existing databases that already have the table are untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bc_redirects')) {
            return;
        }
        Schema::create('bc_redirects', function (Blueprint $table) {
            $table->id();
            $table->string('old_url');
            $table->string('new_url');
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('old_url');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        // Never drop: the table may predate this migration and hold live rules.
    }
};
