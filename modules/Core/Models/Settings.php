<?php
namespace Modules\Core\Models;

use App\BaseModel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Concerns\HasEvents;
use Illuminate\Support\Facades\Log;
use Modules\Language\Models\Language;

class Settings extends BaseModel
{
    use HasEvents;

    protected $table = 'core_settings';
    protected $fillable=['name','group','val','lang'];

    /**
     * Settings keys whose values can vary per locale. Anything not in this list
     * is stored once globally (lang = NULL) — toggles, IDs, colors, emails, etc.
     */
    public const TRANSLATABLE_KEYS = [
        'site_name',
        'site_title',
        'site_tagline',
        'site_desc',
        'topbar_left_text',
        'footer_text_left',
        'footer_text_right',
        'address',
        'from_name',
        // Payment icons can be region-specific
        'payment_custom_icon_url',
        'payment_icon_apple_pay',
        'payment_icon_google_pay',
        'payment_icon_paypal',
        'payment_icon_cards',
        'payment_icon_alipay',
    ];

    public static function isTranslatable(string $key): bool
    {
        return in_array($key, self::TRANSLATABLE_KEYS, true);
    }

    /**
     * Read settings for a group with optional locale overlay.
     *
     * Default-row matching is permissive on purpose: the original code never
     * filtered by lang, so existing rows in the wild may have lang = NULL,
     * lang = '', OR lang = the site's main locale (e.g. 'en'). All three are
     * treated as "the default row" for that key. Without this the new code
     * would hide pre-existing settings data.
     *
     * Priority when multiple default rows exist for the same name:
     *   NULL  >  empty string  >  main_lang
     *
     * When $locale is non-default, locale-specific rows overlay the defaults.
     */
    public static function getSettings($group = '', $locale = '')
    {
        $mainLang = function_exists('get_main_lang') ? get_main_lang() : 'en';

        $query = static::query();
        if ($group) {
            $query->where('group', $group);
        }
        // Default rows: lang null/empty/main_lang — sorted so NULL/empty win over main_lang
        $defaults = (clone $query)
            ->where(function ($q) use ($mainLang) {
                $q->whereNull('lang')
                  ->orWhere('lang', '')
                  ->orWhere('lang', $mainLang);
            })
            ->orderByRaw("CASE WHEN lang IS NULL THEN 0 WHEN lang = '' THEN 1 ELSE 2 END")
            ->get();

        $res = [];
        foreach ($defaults as $row) {
            // First (highest-priority) row for each name wins
            if (!array_key_exists($row->name, $res)) {
                $res[$row->name] = $row->val;
            }
        }

        $isMultiLang = !empty($locale) && (function_exists('is_default_lang') ? !is_default_lang($locale) : ($locale !== $mainLang));
        if ($isMultiLang) {
            $localized = static::query()
                ->when($group, fn ($q) => $q->where('group', $group))
                ->where('lang', $locale)
                ->get();
            foreach ($localized as $row) {
                if ($row->val !== null && $row->val !== '') {
                    $res[$row->name] = $row->val;
                }
            }
        }

        return $res;
    }

    public static function item($item, $default = false)
    {
        $key = 'setting_' . $item;

        if (Cache::has($key)) {
            $value = Cache::get($key);
        } else {
            try {
                $val = Settings::where('name', $item)->first();
                $value = $val ? $val['val'] : '';
                Cache::forever($key, $value);
            } catch (\Exception $e) {
                $value = $default;
                // Log the error for debugging purposes
                Log::warning("Settings::item connection failed for '{$item}': " . $e->getMessage());
            }
        }

        return (empty($value) and strlen($value)===0)?$default:$value;
    }

    /**
     * Store a setting. When $locale is non-default AND the key is translatable,
     * the value is saved into a per-locale row (lang = $locale) so the default
     * row stays intact and falls back when no translation exists.
     *
     * For non-translatable keys, $locale is ignored (they are global).
     */
    public static function store($key, $data, $group = 'general', $locale = null)
    {
        $value = is_array($data) ? json_encode($data) : $data;

        $isMultiLang = !empty($locale) && (function_exists('is_default_lang') ? !is_default_lang($locale) : $locale !== 'en');
        $useLocaleRow = $isMultiLang && self::isTranslatable($key);

        if ($useLocaleRow) {
            $row = self::query()
                ->where('name', $key)
                ->where('lang', $locale)
                ->first();
            if (!$row) {
                $row = new self();
                $row->name = $key;
                $row->lang = $locale;
            }
            $row->val   = $value;
            $row->group = $group;
            $row->save();
        } else {
            // Default (global) row: match the same permissive set as getSettings
            // so we update existing legacy rows instead of creating duplicates.
            $mainLang = function_exists('get_main_lang') ? get_main_lang() : 'en';
            $row = self::query()
                ->where('name', $key)
                ->where(function ($q) use ($mainLang) {
                    $q->whereNull('lang')
                      ->orWhere('lang', '')
                      ->orWhere('lang', $mainLang);
                })
                ->orderByRaw("CASE WHEN lang IS NULL THEN 0 WHEN lang = '' THEN 1 ELSE 2 END")
                ->first();
            if (!$row) {
                $row = new self();
                $row->name = $key;
                $row->lang = null;
            }
            // Don't rewrite the lang column on existing rows — preserve whatever it was
            $row->val   = $value;
            $row->group = $group;
            $row->save();
        }

        Cache::forget('setting_' . $key);
    }

    public static function getSettingPages($forMenu = false){
        $allSettings = [
            'general'=>[
                'id'=>'general',
                'title' => __("General Settings"),
                'position' => 10,
                'keys' => [
                    'site_title',
                    'site_name',
                    'site_desc',
                    'site_tagline',
                    'site_favicon',
                    'logo_id',
                    'logo_url',
                    'favicon_url',
                    'home_page_id',
                    'topbar_left_text',
                    'footer_text_left',
                    'footer_text_right',
                    'list_widget_footer',
                    'date_format',
                    'site_timezone',
                    'site_locale',
                    'site_first_day_of_the_weekin_calendar',
                    'site_enable_multi_lang',
                    'enable_rtl',
                    'page_contact_title',
                    'page_contact_sub_title',
                    'page_contact_desc',
                    'page_contact_image',
                    'home_hotel_id',
                    'admin_email',
                    'from_email',
                    'from_name',
                    'contact_email',
                    'contact_phone',
                    'address',
                    'default_timezone',
                    'enable_payment_apple_pay',
                    'enable_payment_google_pay',
                    'enable_payment_paypal',
                    'enable_payment_cards',
                    'enable_payment_alipay',
                    'enable_payment_alipay',
                    'payment_custom_icon_url',
                    'primary_color',
                    'secondary_color',
                    'header_logo',
                    'footer_logo'
                ]
            ],
        ];

        // Modules
        $custom_modules = \Modules\ServiceProvider::getActivatedModules();
        if(!empty($custom_modules)){
            foreach($custom_modules as $module=>$moduleData){
                $moduleClass = str_replace('ModuleProvider','SettingClass',$moduleData['class']);
                if(!class_exists($moduleClass) and !empty($moduleData['parent'])){
                    $moduleClass = str_replace('ModuleProvider','SettingClass',$moduleData['parent']);
                }
                if(class_exists($moduleClass))
                {
                    $blockConfig = call_user_func([$moduleClass,'getSettingPages']);
                    if(!empty($blockConfig)){
                        foreach ($blockConfig as $k=>$v){
                            $allSettings[$v['id']] = $v;
                        }
                    }
                }
            }
        }
        //Custom
        $custom_modules = \Custom\ServiceProvider::getModules();
        if(!empty($custom_modules)){
            foreach($custom_modules as $module){
                $moduleClass = "\\Custom\\".ucfirst($module)."\\SettingClass";
                if(class_exists($moduleClass))
                {
                    $blockConfig = call_user_func([$moduleClass,'getSettingPages']);
                    if(!empty($blockConfig)){
                        foreach ($blockConfig as $k=>$v){
                            $allSettings[$v['id']] = $v;
                        }
                    }
                }
            }
        }
        //Plugins
        $plugins_modules = \Plugins\ServiceProvider::getModules();
        if(!empty($plugins_modules)){
            foreach($plugins_modules as $module){
                $moduleClass = "\\Plugins\\".ucfirst($module)."\\SettingClass";
                if(class_exists($moduleClass))
                {
                    $blockConfig = call_user_func([$moduleClass,'getSettingPages']);
                    if(!empty($blockConfig)){
                        foreach ($blockConfig as $k=>$v){
                            $allSettings[$v['id']] = $v;
                        }
                    }
                }
            }
        }
        //Pro
        $plugins_modules = get_pro_modules();
        if (!empty($plugins_modules) and isPro()) {
            foreach ($plugins_modules as $module) {
                $moduleClass = "\\Pro\\" . ucfirst($module) . "\\SettingClass";
                if (class_exists($moduleClass)) {
                    $blockConfig = call_user_func([$moduleClass, 'getSettingPages']);
                    if (!empty($blockConfig)) {
                        foreach ($blockConfig as $k => $v) {
                            $allSettings[$v['id']] = $v;
                        }
                    }
                }
            }
        }
        //@todo Sort items by Position
        $allSettings = array_values(\Illuminate\Support\Arr::sort($allSettings, function ($value) {
            return $value['position'] ?? 0;
        }));

        if(!empty($allSettings)){
            foreach ($allSettings as $k=>$item)
            {
                if(!empty($item['hide_in_settings_menu']) and $forMenu){
                    unset($allSettings[$k]);
                    continue;
                }
                $item['url'] = route('core.admin.settings.index',['group'=>$item['id']]);
                $item['name'] = $item['title'] ?? $item['id'];
                $item['icon'] = $item['icon'] ?? '';

                $allSettings[$k] = $item;
            }
        }
        return $allSettings;
    }
    public static function clearCustomCssCache(){
        $langs = Language::getActive();
        if(!empty($langs)){
            foreach ($langs as $lang)
            {
                Cache::forget("custom_css_". config('bc.active_theme').'_' .$lang->locale);
            }
        }
    }
}
