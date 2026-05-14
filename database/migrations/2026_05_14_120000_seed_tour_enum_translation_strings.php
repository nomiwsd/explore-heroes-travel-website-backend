<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Seed static enum/label strings used by the Tours module so they appear
     * in the admin Translation Manager. Admin can then translate them to any
     * language from the dashboard.
     *
     * We only insert the `raw` key + `en` row here — no other locales — so the
     * admin sees them as "Not Translated" and fills them in themselves.
     */
    public function up(): void
    {
        if (!Schema::hasTable('core_translations')) {
            return;
        }

        $strings = [
            // tour_type enum values
            'Tailor Made',
            'Fixed Departure',
            'Group Tour',
            'Private Tour',
            'Day Trip',
            'Getaway',

            // suitable_for enum values
            'Family',
            'Couple',
            'Friends',
            'Solo',
            'Senior',
            'Honeymoon',
            'Corporate',

            // Tour Summary section labels
            'Tour Summary',
            'Suitable for',
            'Inclusion',
            'Tour Theme',
            'Number of Cities',
            'View More >>',
            'View Less <<',

            // Tour card / sidebar labels
            'Starting From',
            'From',
            'USD',
            'Days',
            'Hours',
            'Nights',
            'Off',
            'Destination',
            'Type of tour',
            'Show Inclusions',
            'Starting at',
            'Per Group',
            'Get a Quote',
            "Hello! I'm",
            'Related',
            'Inquire Now',

            // Itinerary labels
            'Day',
            'Activities',
            'Highlights',
            'Accommodation',
            'Meal Plan',
            'Travel Time',
            'Transport Mode',

            // Meal plan codes
            'Bed & Breakfast',
            'Half Board',
            'Full Board',
            'All Inclusive',
            'Room Only',
        ];

        $now = now();

        foreach ($strings as $original) {
            // 1) Insert (or fetch) the raw key row
            $existing = DB::table('core_translations')
                ->where('locale', 'raw')
                ->where('string', $original)
                ->first();

            if ($existing) {
                $parentId = $existing->id;
            } else {
                $parentId = DB::table('core_translations')->insertGetId([
                    'locale' => 'raw',
                    'string' => $original,
                    'parent_id' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // 2) Insert the English row if missing (so admin's "Original" column shows readable text)
            $existingEn = DB::table('core_translations')
                ->where('locale', 'en')
                ->where('parent_id', $parentId)
                ->first();

            if (!$existingEn) {
                DB::table('core_translations')->insert([
                    'locale' => 'en',
                    'string' => $original,
                    'parent_id' => $parentId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Keep translations on rollback
    }
};
