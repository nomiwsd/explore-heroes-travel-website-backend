<?php
namespace Modules\Language\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rule;
use Modules\AdminController;
use Modules\Language\Models\Language;
use Modules\Language\Models\Translation;

class LanguageController extends AdminController
{
    public function index(Request $request)
    {
        $this->checkPermission('language_manage');
        if ($request->isMethod('post') and !empty($request->input())) {
            $this->validate($request,[
                'name'=>'required',
                'flag'=>'required',
                'locale'=>'required'
            ]);
            $check = Language::withTrashed()->where('locale', $request->input('locale'))->first();
            if ($check and $check->trashed()) {
                $check->restore();
                $check->fill($request->input());
                $check->save();
            }else{
                $this->validate($request,[
                    'locale'=>'unique:core_languages,locale'
                ]);
                $row = new Language($request->input());
                $row->save();
            }
            if ($request->wantsJson()) {
                return response()->json(['url' => route('language.admin.index')]);
            }
            return Redirect::route('language.admin.index')->with('success', __("Language created"));
        }
        $listLanguage = Language::query() ;
        if (!empty($search = $request->query('s'))) {
            $listLanguage->where('name', 'LIKE', '%' . $search . '%');
            $listLanguage->Orwhere('locale', 'LIKE', '%' . $search . '%');
        }
        $listLanguage->orderBy('created_at', 'asc');

        // Return JSON for API requests
        if ($request->wantsJson() || $request->expectsJson()) {
            /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
            $paginator = $listLanguage->paginate(20);

            // Calculate total raw strings once
            $totalStrings = \Modules\Language\Models\Translation::where('locale', 'raw')->count();

            $paginator->getCollection()->transform(function ($lang) use ($totalStrings) {
                $translatedCount = \Modules\Language\Models\Translation::from('core_translations as t1')
                    ->join('core_translations as t2', 't1.parent_id', '=', 't2.id')
                    ->where('t1.locale', $lang->locale)
                    ->where('t2.locale', 'raw')
                    ->whereRaw("IFNULL(t1.string,'') != ''")
                    ->count();

                $lang->translated_strings = $translatedCount;
                $lang->total_strings = $totalStrings;
                $lang->progress = $totalStrings > 0 ? round(($translatedCount / $totalStrings) * 100) : 0;
                return $lang;
            });

            return response()->json($paginator);
        }

        $data = [
            'rows'        => $listLanguage->paginate(20),
            'row'         => new Language(),
            'locales'     => config('languages.locales'),
            'breadcrumbs' => [
                [
                    'name'  => __('Language Management'),
                    'class' => 'active'
                ],
            ]
        ];
        $this->setActiveMenu(route('core.admin.tool.index'));
        return view('Language::admin.language.index', $data);
    }

    public function edit(Request $request, $id)
    {
        $this->checkPermission('language_manage');

        $row = Language::find($id);

        if (empty($row)) {
            return redirect(route('language.admin.index'));
        }


        if (!empty($request->input())) {

            $this->validate($request,[
                'name'=>'required',
                'flag'=>'required',
                'locale'=>[
                    'required',
                    Rule::unique('core_languages')->ignore($row->id)
                ]
            ]);

            $row->fill($request->input());

            Cache::forget('locale_active_0');
            Cache::forget('locale_active_1');

            if ($row->save()) {
                if ($request->wantsJson() || $request->expectsJson()) {
                    return response()->json([
                        'message' => __('Language updated'),
                        'data' => $row
                    ]);
                }
                return Redirect::back()->with('success', __('Language updated'));
            }
        }

        // Return JSON for API requests
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'data' => $row,
                'locales' => config('languages.locales')
            ]);
        }

        $data = [
            'row'         => $row,
            'locales'     => config('languages.locales'),
            'breadcrumbs' => [
                [
                    'name' => __('Languages'),
                    'url'  => route('language.admin.index')
                ],
                [
                    'name'  => __('Edit: :name', ['name' => $row->name]),
                    'class' => 'active'
                ],
            ]
        ];
        $this->setActiveMenu(route('core.admin.tool.index'));
        return view('Language::admin.language.detail', $data);
    }

    public function setDefault(Request $request)
    {
        $this->checkPermission('language_manage');
        $id = $request->input('id');
        $lang = Language::find($id);
        if (!$lang) {
            return response()->json(['status' => 0, 'message' => __('Language not found')]);
        }

        // Update site_locale setting
        if (function_exists('setting_update_item')) {
            setting_update_item('site_locale', $lang->locale);
        } else {
            DB::table('core_settings')->updateOrInsert(['name' => 'site_locale'], ['val' => $lang->locale]);
        }

        // Update is_default column
        Language::where('id', '!=', $id)->update(['is_default' => 0]);
        $lang->is_default = 1;
        $lang->save();

        Cache::forget('locale_active_0');
        Cache::forget('locale_active_1');

        return response()->json(['status' => 1, 'message' => __('Default language updated')]);
    }

    public function bulkEdit(Request $request)
    {
        $this->checkPermission('language_manage');

        $ids = $request->input('ids');
        $action = $request->input('action');
        if (empty($ids) or !is_array($ids)) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 0, 'message' => __("Select at least 1 item!")]);
            }
            return Redirect::back()->with('error', __("Select at least 1 item!"));
        }
        if (empty($action)) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 0, 'message' => __('Select an Action!')]);
            }
            return Redirect::back()->with('error', __('Select an Action!'));
        }
        if ($action == "delete") {
            foreach ($ids as $id) {
                $query = Language::where("id", $id)->first();
                if(!empty($query)){
                    $query->delete();
                }
            }
        } else {
            foreach ($ids as $id) {
                $query = Language::where("id", $id);
                $query->update(['status' => $action]);
            }
        }
        Cache::forget('locale_active_0');
        Cache::forget('locale_active_1');
        if ($request->wantsJson()) {
            return response()->json(['status' => 1, 'message' => __('Updated success!')]);
        }
        return Redirect::back()->with('success', __('Updated success!'));
    }
}
