<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add missing fields for Tours module enhancement
     */
    public function up(): void
    {
        Schema::table('bc_tours', function (Blueprint $table) {
            // Basic Info - Missing fields
            $table->integer('nights')->nullable();
            $table->string('tour_type', 100)->nullable();
            $table->json('suitable_for')->nullable();
            $table->json('tour_themes')->nullable();
            $table->json('cities_covered')->nullable();
            $table->json('summary_inclusions')->nullable();
            $table->unsignedBigInteger('tour_expert_id')->nullable();

            // Details tab - Policies
            $table->text('conditions')->nullable();
            $table->text('cancellation_policy')->nullable();
            $table->text('child_policy')->nullable();
            $table->text('payment_terms')->nullable();

            // Media tab - Hero slider and Map
            $table->json('hero_slider')->nullable();
            $table->text('map_embed')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bc_tours', function (Blueprint $table) {
            $table->dropColumn([
                'nights',
                'tour_type',
                'suitable_for',
                'tour_themes',
                'cities_covered',
                'summary_inclusions',
                'tour_expert_id',
                'conditions',
                'cancellation_policy',
                'child_policy',
                'payment_terms',
                'hero_slider',
                'map_embed',
            ]);
        });
    }
};
