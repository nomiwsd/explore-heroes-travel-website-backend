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
        Schema::table('bc_contact', function (Blueprint $table) {
            // Check if columns don't exist before adding
            if (!Schema::hasColumn('bc_contact', 'form_type')) {
                $table->string('form_type', 50)->default('contact')->after('phone');
            }
            if (!Schema::hasColumn('bc_contact', 'tour_id')) {
                $table->unsignedBigInteger('tour_id')->nullable()->after('form_type');
            }
            if (!Schema::hasColumn('bc_contact', 'subject')) {
                $table->string('subject', 255)->nullable()->after('tour_id');
            }
            if (!Schema::hasColumn('bc_contact', 'travel_date')) {
                $table->date('travel_date')->nullable()->after('message');
            }
            if (!Schema::hasColumn('bc_contact', 'number_of_people')) {
                $table->integer('number_of_people')->nullable()->after('travel_date');
            }
            if (!Schema::hasColumn('bc_contact', 'special_requirements')) {
                $table->text('special_requirements')->nullable()->after('number_of_people');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bc_contact', function (Blueprint $table) {
            $table->dropColumn([
                'form_type',
                'tour_id',
                'subject',
                'travel_date',
                'number_of_people',
                'special_requirements'
            ]);
        });
    }
};
