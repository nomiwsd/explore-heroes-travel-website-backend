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
            // Check and add each column if it doesn't exist
            if (!Schema::hasColumn('bc_review', 'author_name')) {
                $table->string('author_name')->nullable()->after('author_id');
            }
            if (!Schema::hasColumn('bc_review', 'author_email')) {
                $table->string('author_email')->nullable()->after('author_name');
            }
            if (!Schema::hasColumn('bc_review', 'author_avatar')) {
                $table->string('author_avatar')->nullable()->after('author_email');
            }
            if (!Schema::hasColumn('bc_review', 'author_location')) {
                $table->string('author_location')->nullable()->after('author_avatar');
            }
            if (!Schema::hasColumn('bc_review', 'author_country')) {
                $table->string('author_country')->nullable()->after('author_location');
            }
            if (!Schema::hasColumn('bc_review', 'review_source')) {
                $table->string('review_source')->nullable()->after('author_country');
            }
            if (!Schema::hasColumn('bc_review', 'review_date')) {
                $table->date('review_date')->nullable()->after('review_source');
            }
            if (!Schema::hasColumn('bc_review', 'trip_summary')) {
                $table->text('trip_summary')->nullable()->after('review_date');
            }
            if (!Schema::hasColumn('bc_review', 'agent_name')) {
                $table->string('agent_name')->nullable()->after('trip_summary');
            }
            if (!Schema::hasColumn('bc_review', 'agent_role')) {
                $table->string('agent_role')->nullable()->after('agent_name');
            }
            if (!Schema::hasColumn('bc_review', 'agent_photo')) {
                $table->string('agent_photo')->nullable()->after('agent_role');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bc_review', function (Blueprint $table) {
            $columns = [
                'author_name', 'author_email', 'author_avatar', 'author_location',
                'author_country', 'review_source', 'review_date', 'trip_summary',
                'agent_name', 'agent_role', 'agent_photo'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('bc_review', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
