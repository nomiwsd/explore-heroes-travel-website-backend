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
        Schema::table('core_pages', function (Blueprint $table) {
            // SEO fields
            if (!Schema::hasColumn('core_pages', 'meta_title')) {
                $table->string('meta_title')->nullable()->after('title');
            }
            if (!Schema::hasColumn('core_pages', 'meta_desc')) {
                $table->text('meta_desc')->nullable()->after('meta_title');
            }
            if (!Schema::hasColumn('core_pages', 'meta_keywords')) {
                $table->string('meta_keywords')->nullable()->after('meta_desc');
            }
            
            // Homepage flag
            if (!Schema::hasColumn('core_pages', 'is_homepage')) {
                $table->boolean('is_homepage')->default(false)->after('status');
            }
            
            // Show in header
            if (!Schema::hasColumn('core_pages', 'show_in_header')) {
                $table->boolean('show_in_header')->default(false)->after('show_in_menu');
            }
            
            // Show in footer
            if (!Schema::hasColumn('core_pages', 'show_in_footer')) {
                $table->boolean('show_in_footer')->default(false)->after('show_in_header');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('core_pages', function (Blueprint $table) {
            $columns = ['meta_title', 'meta_desc', 'meta_keywords', 'is_homepage', 'show_in_header', 'show_in_footer'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('core_pages', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
