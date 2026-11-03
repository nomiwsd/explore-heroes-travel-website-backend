<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Keeps the previous value of every core_settings row a data migration
 * changes, so production content is never lost and the migration can be
 * rolled back exactly.
 *
 * Rows are stored in bc_settings_backups, tagged with the migration name.
 */
class SettingsBackup
{
    public const TABLE = 'bc_settings_backups';

    public static function ensureTable(): void
    {
        if (Schema::hasTable(self::TABLE)) {
            return;
        }
        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->id();
            $table->string('migration', 191)->index();
            $table->unsignedBigInteger('setting_id')->index();
            $table->string('name', 191);
            $table->longText('val')->nullable();
            $table->timestamp('backed_up_at')->nullable();
        });
    }

    /** Back up a core_settings row (once per migration) and then set its new value. */
    public static function update(string $migration, object $row, $newVal): void
    {
        self::ensureTable();
        $exists = DB::table(self::TABLE)
            ->where('migration', $migration)
            ->where('setting_id', $row->id)
            ->exists();
        if (!$exists) {
            DB::table(self::TABLE)->insert([
                'migration' => $migration,
                'setting_id' => $row->id,
                'name' => $row->name,
                'val' => $row->val,
                'backed_up_at' => now(),
            ]);
        }
        DB::table('core_settings')->where('id', $row->id)->update(['val' => $newVal]);
        Cache::forget('setting_' . $row->name);
    }

    /** Put back the values saved by a migration. The backup rows are kept. */
    public static function restore(string $migration): void
    {
        if (!Schema::hasTable(self::TABLE)) {
            return;
        }
        foreach (DB::table(self::TABLE)->where('migration', $migration)->get() as $b) {
            DB::table('core_settings')->where('id', $b->setting_id)->update(['val' => $b->val]);
            Cache::forget('setting_' . $b->name);
        }
    }
}
