<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const NAME = '2026_11_03_000002_disable_vendor_and_user_plans_settings';

    /**
     * ST-09: turn off vendor registration and user pricing plans.
     * Only updates rows that already exist; never inserts. Previous values
     * are kept in bc_settings_backups (see App\Support\SettingsBackup).
     */
    public function up(): void
    {
        if (!Schema::hasTable('core_settings')) {
            return;
        }
        $rows = DB::table('core_settings')
            ->whereIn('name', ['vendor_enable', 'user_plans_enable'])
            ->get(['id', 'name', 'val']);
        foreach ($rows as $row) {
            if ((string) $row->val !== '0') {
                \App\Support\SettingsBackup::update(self::NAME, $row, '0');
            }
        }
    }

    public function down(): void
    {
        \App\Support\SettingsBackup::restore(self::NAME);
    }
};
