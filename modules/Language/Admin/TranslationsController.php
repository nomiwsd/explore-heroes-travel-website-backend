<?php
namespace Modules\Language\Admin;

use function Clue\StreamFilter\fun;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Modules\AdminController;
use Modules\Language\Models\Language;
use Modules\Language\Models\Translation;
use Symfony\Component\Finder\Finder;

class TranslationsController extends AdminController
{
    public function index()
    {
        $this->checkPermission('language_translation');
        $data = [
            'page_title' => __('Translation Manager'),
            'languages'  => Language::paginate(20),
            'total_text' => Translation::where('locale', 'raw')->count()
        ];
        $this->setActiveMenu(route('core.admin.tool.index'));
        return view('Language::translations.index', $data);
    }

    public function detail(Request $request, $id)
    {

        $this->checkPermission('language_translation');
        $lang = Language::find($id);
        if (empty($lang)) {
            abort(404);
        }
        $query = Translation::select([
            'core_translations.*',
            't.string as translate'
        ]);

        $query->where('core_translations.locale', 'raw');

        $query->leftJoin('core_translations as t', function ($join) use ($lang) {

            $join->on('t.parent_id', '=', 'core_translations.id');
            $join->where('t.locale', '=', $lang->locale);
        });
        if ($request->type) {
            switch ($request->type) {

                case "not_translated":
                    $query->whereRaw("(t.id is null or IFNULL(t.string,'') = '' )");
                    break;
                case "translated":
                    $query->whereRaw("IFNULL(t.string,'') != '' ");
                    break;
            }
        }

        if( $request->search_by == "translated_text"){
            if ($request->s) {
                $query->where('t.string', 'like', '%' . $request->s . '%');
            }
        }else{
            if ($request->s) {
                $query->where('core_translations.string', 'like', '%' . $request->s . '%');
            }
        }

        $origins = $query->orderBy('core_translations.string', 'asc')->paginate(30);
        $origins->appends($request->query());
        $data = [
            'page_title'  => __('Translation Manager'),
            'origins'     => $origins,
            'lang'        => $lang,
            'breadcrumbs' => [
                [
                    'name' => __('Translation Manager'),
                    'url'  => route('language.admin.translations.index')
                ],
                [
                    'name'  => __('Translate for: :name', ['name' => $lang->name]),
                    'class' => 'active'
                ],
            ]
        ];
        $this->setActiveMenu(route('core.admin.tool.index'));
        return view('Language::translations.detail', $data);
    }

    public function store(Request $request, $id)
    {

        $this->checkPermission('language_translation');
        $lang = Language::find($id);
        if (empty($lang)) {
            abort(404);
        }
        $translate = $request->input('translate');
        if (is_array($translate)) {
            foreach ($translate as $item_id => $string) {
                $check = Translation::where('locale', $lang->locale)->where('parent_id', $item_id)->first();
                if ($check) {
                    $check->string = $string;
                    $check->save();
                } else {

                    $check = new Translation();
                    $check->parent_id = $item_id;
                    $check->string = $string;
                    $check->locale = $lang->locale;
                    $check->save();
                }
            }
        }
        return Redirect::back()->with('success', __("Translation saved"));
    }

    public function build($id)
    {
        $this->checkPermission('language_translation');
        $back = route('language.admin.translations.index');

        $lang = Language::find($id);
        if (empty($lang)) {
            abort(404);
        }
        $file = base_path('resources/lang/' . $lang->locale . '.json');
        if (!is_writable(base_path('resources/lang'))) {
            return Redirect::to($back)->with('error', __("Folder: resources/lang is not write-able. Please contact your hosting provider"));
        }
        if (file_exists($file) and !is_writable($file)) {
            return Redirect::to($back)->with('error', __("File: :file_name is not write-able. Please contact your hosting provider", ['file_name' => 'resources/lang/' . $lang->locale . '.json']));
        }
        $query = Translation::select([
            'core_translations.*',
            't.string as origin'
        ])->where('core_translations.locale', $lang->locale)->whereRaw("IFNULL(core_translations.string,'') != '' ");
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
        $myfile = fopen($file, "w");
        fwrite($myfile, json_encode($json));
        fclose($myfile);
        $lang->last_build_at = date('Y-m-d H:i:s');
        $lang->save();
        return Redirect::to(route('language.admin.translations.index'))->with('success', __("Re-build language file for: :name success", ['name' => $lang->name]));
    }

    public function loadStrings(){

        $this->checkPermission('language_translation');

        $file = base_path('resources/lang/default.json');
        $back = route('language.admin.translations.index');

        if(!is_file($file)){
            return Redirect::to($back)->with('error', __("Default language source does not exists"));
        }

        $content = file_get_contents($file);
        if(empty($content)){
            return Redirect::to($back)->with('error', __("Default language source empty"));
        }

        $json = json_decode($content, true);
        if(empty($json)){
            return Redirect::to($back)->with('error', __("Default language source do not have any strings"));
        }


        $all_string = Translation::select("string")->where("locale","raw")->get()->pluck('string')->toArray();
        $all_string = array_flip($all_string);

        foreach ($json as $key=>$value) {
            // Split the group and item
            if(empty($all_string[ $key ])){
                $lang =  new Translation([
                    'locale' => 'raw',
                    'string' => $key
                ]);
                $lang->save();
            }
        }

        return Redirect::to($back)->with('success', __("Loaded :count strings", ['count' => count($json)]));
    }
    public function genDefault(){

        $back = route('language.admin.translations.index');
        $file = base_path('resources/lang/default.json');
        if (!is_writable(base_path('resources/lang'))) {
            return Redirect::to($back)->with('error', __("Folder: resources/lang is not write-able. Please contact your hosting provider"));
        }
        if (file_exists($file) and !is_writable($file)) {
            return Redirect::to($back)->with('error', __("File: :file_name is not write-able. Please contact your hosting provider"));
        }
        $query = Translation::select([
            'core_translations.*',
        ])->where('core_translations.locale', 'raw');
        $json = [];
        $rows = $query->get();
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $json[$row['string']] = '';
            }
        }
        $myfile = fopen($file, "w");
        fwrite($myfile, json_encode($json));
        fclose($myfile);

        return Redirect::to($back)->with('success', __("Generate Default JSON Language"));
    }

    public function findTranslations($path = null)
    {
        $path = $path ? : base_path();
        $keys = array();
        $functions = array(
            'trans',
            'trans_choice',
            'Lang::get',
            'Lang::choice',
            'Lang::trans',
            'Lang::transChoice',
            '@lang',
            '@choice',
            'transEditable',
            '__',
            't' // Added t() for frontend
        );
        $pattern =                              // See http://regexr.com/392hu
            "[^\w]" .                          // Must not have an alphanum or _ or > before real method
            "(" . implode('|', $functions) . ")" .  // Must start with one of the functions
            "\(" .                               // Match opening parenthese
            "[\'\"]" .                           // Match " or '
            "(" .                                // Start a new group to match:
                ".+".               // Must start with group
//            "([^\1)]+)+" .                // Be followed by one or more items/keys
            ")" .                                // Close group
            "[\'\"]" .                           // Closing quote
            "[\),]";                            // Close parentheses or new parameter

        // 1. Scan Backend (PHP)
        $finder = new Finder();
        $finder->in($path)->exclude('storage')
            ->exclude('node_modules')
            ->exclude('public')
            ->exclude('test')
            ->name('*.php')->files();

        foreach ($finder as $file) {
            if (preg_match_all("/$pattern/siU", $file->getContents(), $matches)) {
                foreach ($matches[2] as $key) {
                    if(!$key) continue;
                    $keys[] = $key;
                }
            }
        }

        // 2. Scan Frontend (TSX/TS) - Configurable Path
        // In local dev: sibling directory. In prod: might need to be set via env or skipped if not accessible
        $frontendPathEnv = env('FRONTEND_PATH', '../explore-heros-travel-website');
        $frontendPath = base_path($frontendPathEnv);

        if (is_dir($frontendPath)) {
            $frontendFinder = new Finder();
            $frontendFinder->in($frontendPath)
                ->exclude('node_modules')
                ->exclude('.next')
                ->exclude('public')
                ->name(['*.tsx', '*.ts', '*.js', '*.jsx'])
                ->files();

            foreach ($frontendFinder as $file) {
                // Modified regex to capture both key and optional default text: t('key', 'default')
                if (preg_match_all("/\bt\(\s*['\"]([^'\"]+)['\"]\s*(?:,\s*['\"]([^'\"]+)['\"])?/siU", $file->getContents(), $matches)) {
                    foreach ($matches[1] as $index => $key) {
                        if (!$key) continue;
                        $defaultText = !empty($matches[2][$index]) ? $matches[2][$index] : $key;
                        $keys[$key] = $defaultText;
                    }
                }
            }
        }

        // Add the translations to the database, if not existing.
        $all_string = Translation::select("string", "id")->where("locale", "raw")->get()->pluck('id', 'string')->toArray();

        foreach ($keys as $key => $defaultText) {
            if(empty($all_string[ $key ])){
                $raw = new Translation([
                    'locale' => 'raw',
                    'string' => $key
                ]);
                $raw->save();
                $parentId = $raw->id;
            } else {
                $parentId = $all_string[$key];
            }

            // Auto-fill English (en) if it doesn't exist
            $checkEn = Translation::where('locale', 'en')->where('parent_id', $parentId)->first();
            if (!$checkEn) {
                $en = new Translation([
                    'locale' => 'en',
                    'string' => $defaultText,
                    'parent_id' => $parentId
                ]);
                $en->save();
            }
        }

        // Return the number of found translations
        return count($keys);
    }

    public function loadTranslateJson(Request $request)
    {
        $locale_name = $request->input('locale');
        if (!$locale_name) {
            return response()->json(['error' => 'Locale is required'], 400);
        }

        $file = base_path('resources/lang/' . $locale_name . '.json');

        // If not in backend lang, try frontend messages
        if (!is_file($file)) {
            $file = base_path('../explore-heros-travel-website/messages/' . $locale_name . '.json');
        }

        if (!is_file($file)) {
            return response()->json(['error' => "Translation file ({$locale_name}.json) not found"], 404);
        }

        $content = file_get_contents($file);
        $json = json_decode($content, true);
        if (empty($json)) {
            return response()->json(['error' => "Translation file is empty or invalid JSON"], 400);
        }

        $all_raw = Translation::where("locale", "raw")->get()->pluck("id", "string")->toArray();
        $imported = 0;

        foreach ($json as $key => $value) {
            // Ensure raw key exists
            if (empty($all_raw[$key])) {
                $raw = new Translation([
                    'locale' => 'raw',
                    'string' => $key
                ]);
                $raw->save();
                $parentId = $raw->id;
                $all_raw[$key] = $parentId;
            } else {
                $parentId = $all_raw[$key];
            }

            // Update or create translation for locale
            $check = Translation::where("locale", $locale_name)->where("parent_id", $parentId)->first();
            if ($check) {
                $check->string = $value;
                $check->save();
            } else {
                $create = new Translation([
                    'locale' => $locale_name,
                    'string' => $value,
                    "parent_id" => $parentId
                ]);
                $create->save();
            }
            $imported++;
        }

        return response()->json([
            'success' => true,
            'message' => "Imported {$imported} strings for {$locale_name}",
            'count' => $imported
        ]);
    }

    /**
     * API: Get translations for a locale
     */
    public function getTranslationsApi(Request $request, $locale)
    {
        $lang = Language::where('locale', $locale)->first();
        if (empty($lang)) {
            return response()->json(['error' => 'Language not found'], 404);
        }

        $query = Translation::select([
            'core_translations.id',
            'core_translations.string as key',
            'core_translations.string as original',
            't.string as translation'
        ]);

        $query->where('core_translations.locale', 'raw');

        $query->leftJoin('core_translations as t', function ($join) use ($lang) {
            $join->on('t.parent_id', '=', 'core_translations.id');
            $join->where('t.locale', '=', $lang->locale);
        });

        // Filter by type
        if ($request->filter) {
            switch ($request->filter) {
                case "not_translated":
                    $query->whereRaw("(t.id is null or IFNULL(t.string,'') = '' )");
                    break;
                case "translated":
                    $query->whereRaw("IFNULL(t.string,'') != '' ");
                    break;
            }
        }

        // Search
        if ($request->s) {
            $query->where(function($q) use ($request) {
                $q->where('core_translations.string', 'like', '%' . $request->s . '%')
                  ->orWhere('t.string', 'like', '%' . $request->s . '%');
            });
        }

        // Group filter
        if ($request->group) {
            $query->where('core_translations.group', $request->group);
        }

        $perPage = $request->per_page ?? 20;
        $results = $query->orderBy('core_translations.string', 'asc')->paginate($perPage);

        // Calculate stats
        $totalQuery = Translation::where('locale', 'raw')->count();
        $translatedQuery = Translation::where('locale', $lang->locale)
            ->whereRaw("IFNULL(string,'') != ''")
            ->count();

        return response()->json([
            'data' => $results->items(),
            'stats' => [
                'total' => $totalQuery,
                'translated' => $translatedQuery,
                'not_translated' => $totalQuery - $translatedQuery,
                'progress' => $totalQuery > 0 ? round(($translatedQuery / $totalQuery) * 100) : 0
            ],
            'meta' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total()
            ]
        ]);
    }

    /**
     * API: Save translations
     */
    public function saveTranslationsApi(Request $request, $locale)
    {
        $lang = Language::where('locale', $locale)->first();
        if (empty($lang)) {
            return response()->json(['error' => 'Language not found'], 404);
        }

        $translations = $request->input('translations', []);
        $saved = 0;

        foreach ($translations as $item) {
            $key = $item['key'] ?? null;
            $value = $item['value'] ?? '';

            if (!$key) continue;

            // Find the raw translation
            $rawTranslation = Translation::where('locale', 'raw')
                ->where('string', $key)
                ->first();

            if (!$rawTranslation) continue;

            // Find or create the translated version
            $check = Translation::where('locale', $lang->locale)
                ->where('parent_id', $rawTranslation->id)
                ->first();

            if ($check) {
                $check->string = $value;
                $check->save();
            } else {
                $check = new Translation();
                $check->parent_id = $rawTranslation->id;
                $check->string = $value;
                $check->locale = $lang->locale;
                $check->save();
            }
            $saved++;
        }

        return response()->json([
            'success' => true,
            'saved' => $saved,
            'message' => "Saved {$saved} translations"
        ]);
    }

    /**
     * API: Build translations (generate JSON file)
     */
    public function buildTranslationsApi(Request $request, $locale)
    {
        $lang = Language::where('locale', $locale)->first();
        if (empty($lang)) {
            return response()->json(['error' => 'Language not found'], 404);
        }

        // Build the JSON file in resources/lang
        $file = base_path('resources/lang/' . $lang->locale . '.json');

        if (!is_writable(base_path('resources/lang'))) {
            return response()->json([
                'success' => false,
                'error' => 'Folder resources/lang is not writable'
            ], 500);
        }

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

        // Write to resources/lang folder (backend)
        $myfile = fopen($file, "w");
        fwrite($myfile, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        fclose($myfile);

        // Also write to public folder for frontend access (legacy/other uses)
        $publicDir = base_path('public/locales');
        if (!is_dir($publicDir)) {
            mkdir($publicDir, 0755, true);
        }
        $publicFile = $publicDir . '/' . $lang->locale . '.json';
        $publicFileHandle = fopen($publicFile, "w");
        fwrite($publicFileHandle, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        fclose($publicFileHandle);

        // Also write to frontend messages folder (for next-intl)
        $frontendPathEnv = env('FRONTEND_PATH', '../explore-heros-travel-website');
        $frontendMessagesDir = base_path($frontendPathEnv . '/messages');

        if (is_dir($frontendMessagesDir) && is_writable($frontendMessagesDir)) {
            $frontendFile = $frontendMessagesDir . '/' . $lang->locale . '.json';
            $frontendFileHandle = fopen($frontendFile, "w");
            if ($frontendFileHandle) {
                fwrite($frontendFileHandle, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                fclose($frontendFileHandle);
            }
        }

        // Update last build time
        $lang->last_build_at = date('Y-m-d H:i:s');
        $lang->save();

        return response()->json([
            'success' => true,
            'message' => "Translation file built successfully for {$lang->name}",
            'file' => 'resources/lang/' . $lang->locale . '.json',
            'strings_count' => count($json),
            'last_build_at' => $lang->last_build_at
        ]);
    }

    /**
     * API: Get translation stats for a locale
     */
    public function getStatsApi(Request $request, $locale)
    {
        $lang = Language::where('locale', $locale)->first();
        if (empty($lang)) {
            return response()->json(['error' => 'Language not found'], 404);
        }

        $totalQuery = Translation::where('locale', 'raw')->count();
        $translatedQuery = Translation::where('locale', $lang->locale)
            ->whereRaw("IFNULL(string,'') != ''")
            ->count();

        return response()->json([
            'total' => $totalQuery,
            'translated' => $translatedQuery,
            'not_translated' => $totalQuery - $translatedQuery,
            'progress' => $totalQuery > 0 ? round(($translatedQuery / $totalQuery) * 100) : 0
        ]);
    }

    /**
     * API: Export translations
     */
    public function exportTranslations(Request $request, $locale)
    {
        $lang = Language::where('locale', $locale)->first();
        if (empty($lang)) {
            return response()->json(['error' => 'Language not found'], 404);
        }

        $format = $request->format ?? 'json';

        $query = Translation::select([
            'core_translations.*',
            't.string as origin'
        ])->where('core_translations.locale', $lang->locale)
          ->whereRaw("IFNULL(core_translations.string,'') != '' ");

        $query->join('core_translations as t', function ($join) use ($lang) {
            $join->on('t.id', '=', 'core_translations.parent_id');
            $join->where('t.locale', 'raw');
        });

        $translations = [];
        $rows = $query->get();
        foreach ($rows as $row) {
            $translations[$row['origin']] = $row['string'];
        }

        if ($format === 'csv') {
            $output = "key,translation\n";
            foreach ($translations as $key => $value) {
                $output .= '"' . str_replace('"', '""', $key) . '","' . str_replace('"', '""', $value) . "\"\n";
            }
            return response($output)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $locale . '_translations.csv"');
        }

        return response()->json($translations)
            ->header('Content-Disposition', 'attachment; filename="' . $locale . '_translations.json"');
    }

    /**
     * API: Scan for new translatable strings
     */
    public function scanForStringsApi()
    {
        $count = $this->findTranslations();

        return response()->json([
            'success' => true,
            'found' => $count,
            'message' => "Found and added {$count} translatable strings"
        ]);
    }
}
