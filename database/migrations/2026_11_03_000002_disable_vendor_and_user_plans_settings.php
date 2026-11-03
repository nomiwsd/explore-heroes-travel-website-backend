<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ST-09: turn off vendor registration and user pricing plans.
     * Only updates rows that already exist; never inserts.
     */
    public function up(): void
    {
        if (!Schema::hasTable('core_settings')) {
            return;
        }
        foreach (['vendor_enable', 'user_plans_enable'] as $key) {
            DB::table('core_settings')->where('name', $key)->update(['val' => '0']);
            Cache::forget('setting_' . $key);
        }
    }

    public function down(): void
    {
        // Intentionally irreversible (previous values were template defaults).
    }
};
