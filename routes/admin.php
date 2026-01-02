<?php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\App;

// ============================================================
// SPECIFIC API ROUTES - Must be before the catch-all route!
// ============================================================

// Language Translation API Routes
Route::group(['prefix'=>'admin/module/language','middleware' => ['auth:sanctum','dashboard']], function() {
    // Translations API
    Route::get('/translations/{locale}', [\Modules\Language\Admin\TranslationsController::class, 'getTranslationsApi']);
    Route::post('/translations/{locale}/save', [\Modules\Language\Admin\TranslationsController::class, 'saveTranslationsApi']);
    Route::post('/translations/{locale}/build', [\Modules\Language\Admin\TranslationsController::class, 'buildTranslationsApi']);
    Route::get('/translations/{locale}/stats', [\Modules\Language\Admin\TranslationsController::class, 'getStatsApi']);
    Route::get('/translations/{locale}/export', [\Modules\Language\Admin\TranslationsController::class, 'exportTranslations']);
    Route::post('/translations/scan', [\Modules\Language\Admin\TranslationsController::class, 'scanForStringsApi']);
});

// User Management API Routes
Route::group(['prefix'=>'admin/module/user','middleware' => ['auth:sanctum','dashboard']], function() {
    // Users API
    Route::get('/api/users', [\Modules\User\Admin\UserManagementController::class, 'index']);
    Route::get('/api/users/{id}', [\Modules\User\Admin\UserManagementController::class, 'edit']);
    Route::post('/api/users/store', [\Modules\User\Admin\UserManagementController::class, 'store']);
    Route::post('/api/users/store/{id}', [\Modules\User\Admin\UserManagementController::class, 'update']);
    Route::post('/api/users/bulkEdit', [\Modules\User\Admin\UserManagementController::class, 'bulkEdit']);

    // Roles API
    Route::get('/api/roles', [\Modules\User\Admin\RoleManagementController::class, 'index']);
    Route::get('/api/roles/{id}', [\Modules\User\Admin\RoleManagementController::class, 'edit']);
    Route::post('/api/roles/store', [\Modules\User\Admin\RoleManagementController::class, 'store']);
    Route::post('/api/roles/store/{id}', [\Modules\User\Admin\RoleManagementController::class, 'update']);
    Route::post('/api/roles/bulkEdit', [\Modules\User\Admin\RoleManagementController::class, 'bulkEdit']);
    Route::get('/api/permissions', [\Modules\User\Admin\RoleManagementController::class, 'getPermissions']);
    Route::post('/api/roles/{id}/permissions', [\Modules\User\Admin\RoleManagementController::class, 'assignPermissions']);
});

// ============================================================
// DYNAMIC MODULE ROUTING - Catch-all (must be AFTER specific routes)
// ============================================================

// Admin Route - Dynamic module routing
Route::group(['prefix'=>'admin','middleware' => ['auth:sanctum','dashboard']], function() {
    // Dashboard route
    Route::match(['get','post'],'/',function (){
        $module = ucfirst(htmlspecialchars('Dashboard'));
        $controller = ucfirst(htmlspecialchars($module));
        $class = "\\Modules\\$module\\Admin\\";
        $action = 'index';
        if(class_exists($class.$controller.'Controller') && method_exists($class.$controller.'Controller',$action)){
            return App::call($class.$controller.'Controller@'.$action,[]);
        }
        abort(404);
    })->name('admin.dashboard');

    // Dynamic module routing with named routes
    Route::match(['get','post'],'/module/{module}/{controller?}/{action?}/{param1?}/{param2?}/{param3?}',function ($module,$controller = '',$action = '',$param1 = '',$param2 = '',$param3 = ''){
        $module = ucfirst(htmlspecialchars($module));
        $controller = ucfirst(htmlspecialchars($controller));
        $class = "\\Modules\\$module\\Admin\\";
        if(!class_exists($class.$controller.'Controller')){
            $param3 = $param2;
            $param2 = $param1;
            $param1 = $action;
            $action = $controller;
            $controller = $module;
        }
        $action = $action ? $action : 'index';
        if(class_exists($class.$controller.'Controller') && method_exists($class.$controller.'Controller',$action)){
            // Get method parameters to map them correctly
            $reflection = new \ReflectionMethod($class.$controller.'Controller', $action);
            $parameters = $reflection->getParameters();
            $params = array_values(array_filter([$param1,$param2,$param3], function($v) { return $v !== ''; }));

            // Map parameters by name
            $namedParams = [];
            $paramIndex = 0;
            foreach ($parameters as $parameter) {
                // Skip Request parameters as Laravel will inject them automatically
                if ($parameter->getType() && $parameter->getType()->getName() === 'Illuminate\Http\Request') {
                    continue;
                }
                // Map remaining parameters by their names
                if (isset($params[$paramIndex])) {
                    $namedParams[$parameter->getName()] = $params[$paramIndex];
                    $paramIndex++;
                }
            }

            return App::call($class.$controller.'Controller@'.$action, $namedParams);
        }
        abort(404);
    })->name('admin.module');
});

// Named route helpers for backward compatibility
Route::group(['prefix'=>'admin','middleware' => ['auth:sanctum','dashboard']], function() {
    // Tour routes
    Route::match(['get','post'],'/module/tour/tour',function (){ return redirect('/admin/module/tour/tour'); })->name('tour.admin.index');
    Route::match(['get','post'],'/module/tour/tour/edit/{id}',function ($id){ return redirect("/admin/module/tour/tour/edit/$id"); })->name('tour.admin.edit');

    // Location routes
    Route::match(['get','post'],'/module/location/location',function (){ return redirect('/admin/module/location/location'); })->name('location.admin.index');
    Route::match(['get','post'],'/module/location/location/edit/{id}',function ($id){ return redirect("/admin/module/location/location/edit/$id"); })->name('location.admin.edit');

    // News routes
    Route::match(['get','post'],'/module/news/news',function (){ return redirect('/admin/module/news/news'); })->name('news.admin.index');
    Route::match(['get','post'],'/module/news/news/edit/{id}',function ($id){ return redirect("/admin/module/news/news/edit/$id"); })->name('news.admin.edit');

    // Page routes
    Route::match(['get','post'],'/module/page/page',function (){ return redirect('/admin/module/page/page'); })->name('page.admin.index');
    Route::match(['get','post'],'/module/page/page/edit/{id}',function ($id){ return redirect("/admin/module/page/page/edit/$id"); })->name('page.admin.edit');

    // User routes
    Route::match(['get','post'],'/module/user/user',function (){ return redirect('/admin/module/user/user'); })->name('user.admin.index');
    Route::match(['get','post'],'/module/user/role',function (){ return redirect('/admin/module/user/role'); })->name('user.admin.role.index');
    Route::match(['get','post'],'/module/user/verification',function (){ return redirect('/admin/module/user/verification'); })->name('user.admin.verification.index');

    // Vendor routes
    Route::match(['get','post'],'/module/vendor/plan',function (){ return redirect('/admin/module/vendor/plan'); })->name('vendor.admin.plan.index');
    Route::match(['get','post'],'/module/vendor/payout',function (){ return redirect('/admin/module/vendor/payout'); })->name('vendor.admin.payout.index');

    // Review routes
    Route::match(['get','post'],'/module/review/review',function (){ return redirect('/admin/module/review/review'); })->name('review.admin.index');

    // Contact/Forms routes
    Route::match(['get','post'],'/module/contact/contact',function (){ return redirect('/admin/module/contact/contact'); })->name('contact.admin.index');

    // Core routes (Menu, Settings, etc)
    Route::match(['get','post'],'/module/core/menu',function (){ return redirect('/admin/module/core/menu'); })->name('core.admin.menu.index');
    Route::match(['get','post'],'/module/core/settings',function (){ return redirect('/admin/module/core/settings'); })->name('core.admin.settings.index');

    // Media routes
    Route::match(['get','post'],'/module/media/media',function (){ return redirect('/admin/module/media/media'); })->name('media.admin.index');
});
