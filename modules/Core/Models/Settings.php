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
    protected $fillable=['name','group','val'];

    public static function getSettings($group = '',$locale = '')
    {
        if ($group) {
            static::where('group', $group);
        }
        $all = static::groupBy('name')->get();
        $res = [];
        foreach ($all as $row) {
            $res[$row->name] = $row->val;
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

    public static function store($key, $data, $group = 'general')
    {
        $check = Settings::where('name', $key)->first();
        if ($check) {
            $check->val = is_array($data) ? json_encode($data) : $data;
            $check->group = $group;
            $check->save();
        } else {
            $check = new self();
            $check->val = is_array($data) ? json_encode($data) : $data;
            $check->name = $key;
            $check->group = $group;
            $check->save();
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
