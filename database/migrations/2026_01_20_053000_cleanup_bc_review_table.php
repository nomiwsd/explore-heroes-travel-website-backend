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
            $columnsToDrop = [
                'author_ip',
                'vendor_id',
                'create_user',
                'update_user',
                // We keep 'rate_number' as it is core, but mapped to 'rating' in API
                // We keep 'object_id'/'object_model' as they are core for relations
            ];
            
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('bc_review', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bc_review', function (Blueprint $table) {
             // We can re-add them if needed, making them nullable
             if (!Schema::hasColumn('bc_review', 'author_ip')) {
                 $table->string('author_ip')->nullable();
             }
             if (!Schema::hasColumn('bc_review', 'vendor_id')) {
                 $table->integer('vendor_id')->nullable();
             }
             if (!Schema::hasColumn('bc_review', 'create_user')) {
                 $table->integer('create_user')->nullable();
             }
             if (!Schema::hasColumn('bc_review', 'update_user')) {
                 $table->integer('update_user')->nullable();
             }
        });
    }
};
