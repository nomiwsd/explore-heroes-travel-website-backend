<?php
use \Illuminate\Support\Facades\Route;

Route::get('term/getForSelect2','TermController@index')->name('core.admin.term.getForSelect2');
Route::post('markAsRead','NotificationController@markAsRead')->name('core.admin.notification.markAsRead');
Route::post('markAllAsRead','NotificationController@markAllAsRead')->name('core.admin.notification.markAllAsRead');
Route::get('notifications','NotificationController@loadNotify')->name('core.admin.notification.loadNotify');


Route::group(['prefix'=>'updater'],function (){
    Route::get('/','UpdaterController@index')->name('core.admin.updater.index');
    Route::post('/store_license','UpdaterController@storeLicense')->name('core.admin.updater.store_license');
    Route::post('/check_update','UpdaterController@checkUpdate')->name('core.admin.updater.check_update');
    Route::post('/do_update','UpdaterController@doUpdate')->name('core.admin.updater.do_update');
});

Route::get('settings/index/{group}', 'SettingsController@index')->name('core.admin.settings.index');
Route::post('settings/store/{group}', 'SettingsController@store')->name('core.admin.settings.store');

// SEO Management Routes
Route::group(['prefix' => 'seo'], function () {
    Route::get('/global', 'SeoController@getGlobalSeo')->name('core.admin.seo.global');
    Route::post('/global', 'SeoController@updateGlobalSeo')->name('core.admin.seo.updateGlobal');

    Route::get('/redirects', 'SeoController@getRedirects')->name('core.admin.seo.redirects');
    Route::get('/redirects/{id}', 'SeoController@getRedirect')->name('core.admin.seo.redirects.show');
    Route::post('/redirects/store', 'SeoController@createRedirect')->name('core.admin.seo.redirects.store');
    Route::post('/redirects/store/{id}', 'SeoController@updateRedirect')->name('core.admin.seo.redirects.update');
    Route::post('/redirects/bulkEdit', 'SeoController@bulkEditRedirects')->name('core.admin.seo.redirects.bulkEdit');
    Route::post('/redirects/import', 'SeoController@importRedirects')->name('core.admin.seo.redirects.import');

    Route::get('/sitemap', 'SeoController@getSitemapSettings')->name('core.admin.seo.sitemap');
    Route::post('/sitemap', 'SeoController@updateSitemapSettings')->name('core.admin.seo.sitemap.update');

    Route::get('/robots', 'SeoController@getRobotsTxt')->name('core.admin.seo.robots');
    Route::post('/robots', 'SeoController@updateRobotsTxt')->name('core.admin.seo.robots.update');
});

Route::get('tools', 'ToolsController@index')->name('core.admin.tool.index');

Route::group(['prefix' => 'menu'], function () {
    Route::get('/', 'MenuController@index')->name('core.admin.menu.index');
    Route::get('/create', 'MenuController@create')->name('core.admin.menu.create');
    Route::get('/edit/{id}', 'MenuController@edit')->name('core.admin.menu.edit');
    Route::post('/store', 'MenuController@store')->name('core.admin.menu.store');
    Route::post('/getTypes', 'MenuController@getTypes')->name('core.admin.menu.getTypes');
    Route::post('/searchTypeItems','MenuController@searchTypeItems')->name('core.admin.menu.searchTypeItems');

    Route::post('/bulkEdit','MenuController@bulkEdit')->name('core.admin.menu.bulkEdit');

});

Route::group(['prefix'=>'module'],function (){
    Route::get('/','ModuleController@index')->name('core.admin.module.index');
    Route::post('/bulkEdit','ModuleController@bulkEdit')->name('core.admin.module.bulkEdit');
});
