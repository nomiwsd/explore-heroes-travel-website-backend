<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Language\Models\Language;
use Modules\Language\Models\Translation;

class BuildTranslations extends Command
{
    protected $signature = 'translations:build {locale?}';
    protected $description = 'Build translation JSON files for all or specific locale';

    public function handle()
    {
        $locale = $this->argument('locale');

        if ($locale) {
            $languages = Language::where('locale', $locale)->get();
        } else {
            $languages = Language::all();
        }

        if ($languages->isEmpty()) {
            $this->error('No languages found');
            return 1;
        }

        foreach ($languages as $lang) {
            $this->info("Building translations for {$lang->name} ({$lang->locale})...");

            // Build the JSON file
            $file = base_path('resources/lang/' . $lang->locale . '.json');
            $publicFile = base_path('public/locales/' . $lang->locale . '.json');

            // Ensure directories exist
            if (!is_dir(dirname($file))) {
                mkdir(dirname($file), 0755, true);
            }
            if (!is_dir(dirname($publicFile))) {
                mkdir(dirname($publicFile), 0755, true);
            }

            // Query translations
            $query = Translation::select([
                'core_translations.*',
                't.string as origin'
            ])->where('core_translations.locale', $lang->locale)
              ->whereRaw("IFNULL(core_translations.string,'') != '' ");

            $query->join('core_translations as t', function ($join) use ($lang) {
                $join->on('t.id', '=', 'core_translations.parent_id');
                $join->where('t.locale', 'raw');
            });

            $json = [];
            $rows = $query->get();
            if (!empty($rows)) {
                foreach ($rows as $row) {
                    $json[$row['origin']] = $row['string'];
                }
            }

            // Write to resources/lang
            file_put_contents($file, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // Write to public/locales
            file_put_contents($publicFile, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // Update last build time
            $lang->last_build_at = date('Y-m-d H:i:s');
            $lang->save();

            $this->info("  ✓ Built {$lang->locale}.json with " . count($json) . " strings");
        }

        $this->info('Done!');
        return 0;
    }
}
