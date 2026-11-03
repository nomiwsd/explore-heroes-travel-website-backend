<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Location\Support\ContentCleaner;

/**
 * DS-05: strip pasted inline styles / <font> / empty <span> wrappers from
 * existing destination content. Dry-run by default.
 *
 *   php artisan destinations:clean-content           # report only
 *   php artisan destinations:clean-content --apply   # write changes
 */
class CleanDestinationContent extends Command
{
    protected $signature = 'destinations:clean-content {--apply : Write the cleaned HTML back to the database}';

    protected $description = 'Strip inline styles/font/empty span wrappers from destination (location) content and translations';

    public function handle()
    {
        $apply = (bool) $this->option('apply');
        $this->info($apply ? 'APPLY mode: changes will be written.' : 'DRY-RUN (use --apply to write).');

        $total = 0;
        foreach (['bc_locations', 'bc_location_translations'] as $table) {
            if (!Schema::hasTable($table)) {
                $this->warn("Table {$table} not found, skipping.");
                continue;
            }
            $fields = array_values(array_filter(['content', 'short_description'], function ($f) use ($table) {
                return Schema::hasColumn($table, $f);
            }));
            if (!$fields) {
                continue;
            }

            $changedRows = 0;
            DB::table($table)->select(array_merge(['id'], $fields))->orderBy('id')
                ->chunkById(100, function ($rows) use ($table, $fields, $apply, &$changedRows) {
                    foreach ($rows as $row) {
                        $update = [];
                        foreach ($fields as $f) {
                            $orig = $row->$f;
                            if (!is_string($orig) || $orig === '') {
                                continue;
                            }
                            $clean = ContentCleaner::clean($orig);
                            if ($clean !== $orig) {
                                $update[$f] = $clean;
                            }
                        }
                        if (!$update) {
                            continue;
                        }
                        $changedRows++;
                        $this->line(sprintf(
                            '%s #%d: %s',
                            $table,
                            $row->id,
                            implode(', ', array_map(function ($f) use ($row, $update) {
                                return $f . ' (' . strlen($row->$f) . ' -> ' . strlen($update[$f]) . ' bytes)';
                            }, array_keys($update)))
                        ));
                        if ($apply) {
                            DB::table($table)->where('id', $row->id)->update($update);
                        }
                    }
                });

            $this->info("{$table}: {$changedRows} row(s) " . ($apply ? 'cleaned' : 'would be cleaned'));
            $total += $changedRows;
        }

        $this->info("Total: {$total} row(s).");
        return 0;
    }
}
