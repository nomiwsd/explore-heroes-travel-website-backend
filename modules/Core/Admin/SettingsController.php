<?php
namespace Modules\Core\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Modules\AdminController;
use Modules\Core\Hook;
use Modules\Core\Models\Settings;
use Illuminate\Support\Facades\Cache;

class SettingsController extends AdminController
{
    protected $groups = [];

    public function __construct()
    {
        $this->setActiveMenu(route('core.admin.settings.index',['group'=>"general"]));
    }

    public function index(Request $request, $group = 'general')
    {

        if(empty($this->groups)){
            $this->setGroups();
        }

        $this->checkPermission('setting_update');
        $settingsGroupKeys = array_keys($this->groups);
        if (empty($group) or !in_array($group, $settingsGroupKeys)) {
            $group = $settingsGroupKeys[0];
        }

        $group_data = $this->groups[$group];
        $breadcrumbs = [
           [ 'name'=>$group_data['name'] ?? $group_data['title'] ?? $group]
        ];

        if(!empty($group_data['active_menu'])){
            $this->setActiveMenu($group_data['active_menu']);
        }

        if(!empty($group_data['breadcrumbs'])){
            $breadcrumbs = $group_data['breadcrumbs'];
        }

        // Return JSON for API requests
        if ($request->wantsJson() || $request->expectsJson()) {
            $allSettings = Settings::getSettings($group);

            // Whitelist keys for General Settings to avoid sending huge payload
            if ($group === 'general') {
                $allowedKeys = [
                    'site_title',
                    'site_name',
                    'site_desc',
                    'site_tagline',
                    'logo_id',
                    'logo_url',
                    'favicon_id',
                    'favicon_url',
                    'admin_email',
                    'from_email',
                    'from_name',
                    'contact_email',
                    'contact_phone',
                    'address',
                    'date_format',
                    'site_timezone',
                    'site_locale',
                    'default_timezone',
                    'footer_text_left',
                    'footer_text_right',
                    'enable_payment_apple_pay',
                    'enable_payment_google_pay',
                    'enable_payment_paypal',
                    'enable_payment_cards',
                    'enable_payment_alipay',
                    'payment_custom_icon_url',
                    'payment_icon_apple_pay',
                    'payment_icon_google_pay',
                    'payment_icon_paypal',
                    'payment_icon_cards',
                    'payment_icon_alipay',
                ];

                $filteredSettings = [];
                foreach ($allowedKeys as $key) {
                    if (isset($allSettings[$key])) {
                        $filteredSettings[$key] = $allSettings[$key];
                    }
                }
                // Merge with any keys explicitly defined in group config to be safe
                if (!empty($group_data['keys'])) {
                    foreach ($group_data['keys'] as $key) {
                        if (isset($allSettings[$key])) {
                            $filteredSettings[$key] = $allSettings[$key];
                        }
                    }
                }

                $settingsResponse = $filteredSettings;
            } else {
                $settingsResponse = $allSettings;
            }

            return response()->json([
                'settings' => $settingsResponse,
                'group' => $group_data
            ]);
        }

        $data = [
            'current_group' => $group,
            'groups'        => $this->groups,
            'settings'      => Settings::getSettings($group),
            'breadcrumbs'   => $breadcrumbs,
            'page_title'    => $this->groups[$group]['name'] ?? $this->groups[$group]['title'] ?? $group,
            'group'         => $this->groups[$group],
            'enable_multi_lang'=>true
        ];
        return view('Core::admin.settings.index', $data);
    }

    public function store(Request $request, $group)
    {
        if(is_demo_mode()){
            return back()->with('danger', __("DEMO MODE: Disable setting update"));
        }
        if(empty($this->groups)){
            $this->setGroups();
        }

        $this->checkPermission('setting_update');
        $settingsGroupKeys = array_keys($this->groups);
        if (empty($group) or !in_array($group, $settingsGroupKeys)) {
            $group = $settingsGroupKeys[0];
        }
        $group_data = $this->groups[$group];

        $keys = [];
        $htmlKeys = [];
        $filter_demo_mode = [];

        if(!empty($group_data['keys'])) $keys = $group_data['keys'];
        if(!empty($group_data['html_keys'])) $htmlKeys = $group_data['html_keys'];

        $filter_demo_mode = $group_data['filter_demo_mode'] ?? $filter_demo_mode;
        if(!is_demo_mode()){
            $filter_demo_mode = [];
        }

        $lang = $request->input('lang');
        if(is_default_lang($lang)) $lang = false;

        if ($group === 'style') {
            Settings::clearCustomCssCache();
        }

        if (!empty($request->input())) {
            if (!empty($keys)) {
                $all_values = $request->input();
                //If we found callback validate data before save
                if(!empty($group_data['filter_values_callback']) and is_callable($group_data['filter_values_callback']))
                {
                    $all_values = call_user_func($group_data['filter_values_callback'],$all_values,$request);
                }
                foreach ($keys as $key) {
                    if(in_array($key,$filter_demo_mode)){
                        continue;
                    }
                    $setting_key = $key.($lang ? '_'.$lang : '');
                    $val = $all_values[$key] ?? '';
	                if (is_array($val)) {
		                $val = json_encode($val);
	                }
	                if (in_array($key, $htmlKeys)) {
		                $val = clean($val);
	                }
                    setting_update_item($setting_key,$val);
                }
            }

            do_action(Hook::AFTER_SETTING_SAVED, $group_data);
            //Clear Cache for currency
            Session::put('bc_current_currency',"");

            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json([
                    'status' => 1,
                    'message' => __('Settings Saved'),
                    'settings' => Settings::getSettings($group)
                ]);
            }

            return back()->with('success', __('Settings Saved'));
        }
    }


    protected function setGroups(){

        $all = Settings::getSettingPages();

        $res = [];

        if(!empty($all))
        {
            foreach ($all as $item){
                $res[$item['id']] = $item;
            }
        }
        $this->groups = $res;
    }
}
