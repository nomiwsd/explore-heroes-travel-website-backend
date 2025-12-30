<?php
use \Illuminate\Support\Facades\Route;

Route::get('/getForSelect2', 'UserController@getForSelect2')->name('user.admin.getForSelect2');
Route::get('/', 'UserController@index')->name('user.admin.index');
Route::get('/create', 'UserController@create')->name('user.admin.create');
Route::get('/edit/{id}', 'UserController@edit')->name('user.admin.detail');
Route::post('/store/{id}', 'UserController@store')->name('user.admin.store');
Route::post('/bulkEdit', 'UserController@bulkEdit')->name('user.admin.bulkEdit');
Route::get('/password/{id}','UserController@password')->name('user.admin.password');
Route::post('/changepass/{id}','UserController@changepass')->name('user.admin.changepass');
Route::get('/verify-email/{id}','UserController@verifyEmail')->name('user.admin.verifyEmail');

Route::get('/userUpgradeRequest', 'UserController@userUpgradeRequest')->name('user.admin.upgrade');
Route::get('/upgrade/{id}','UserController@userUpgradeRequestApprovedId')->name('user.admin.upgradeId');
Route::post('/userUpgradeRequestApproved', 'UserController@userUpgradeRequestApproved')->name('user.admin.userUpgradeRequestApproved');

// User Management API Routes
Route::group(['prefix' => 'api'], function () {
    Route::get('/users', 'UserManagementController@index')->name('user.admin.api.users');
    Route::get('/users/{id}', 'UserManagementController@edit')->name('user.admin.api.users.show');
    Route::post('/users/store', 'UserManagementController@store')->name('user.admin.api.users.store');
    Route::post('/users/store/{id}', 'UserManagementController@update')->name('user.admin.api.users.update');
    Route::post('/users/bulkEdit', 'UserManagementController@bulkEdit')->name('user.admin.api.users.bulkEdit');

    Route::get('/roles', 'RoleManagementController@index')->name('user.admin.api.roles');
    Route::get('/roles/{id}', 'RoleManagementController@edit')->name('user.admin.api.roles.show');
    Route::post('/roles/store', 'RoleManagementController@store')->name('user.admin.api.roles.store');
    Route::post('/roles/store/{id}', 'RoleManagementController@update')->name('user.admin.api.roles.update');
    Route::post('/roles/bulkEdit', 'RoleManagementController@bulkEdit')->name('user.admin.api.roles.bulkEdit');
    Route::get('/permissions', 'RoleManagementController@getPermissions')->name('user.admin.api.permissions');
    Route::post('/roles/{id}/permissions', 'RoleManagementController@assignPermissions')->name('user.admin.api.roles.permissions');
});

Route::group(['prefix' => 'role'], function () {
    Route::get('/', 'RoleController@index')->name('user.admin.role.index');
    Route::get('/verifyFields', 'RoleController@verifyFields')->name('user.admin.role.verifyFields');
    Route::get('/permission_matrix', 'RoleController@permission_matrix')->name('user.admin.role.permission_matrix');
    Route::get('/create', 'RoleController@create')->name('user.admin.role.create');
    Route::get('/edit/{id}', 'RoleController@edit')->name('user.admin.role.detail');
    Route::post('/store/{id}', 'RoleController@store')->name('user.admin.role.store');
    Route::post('/verifyFieldsStore', 'RoleController@verifyFieldsStore')->name('user.admin.role.verifyFieldsStore');
    Route::get('/verifyFieldsEdit/{id}', 'RoleController@verifyFieldsEdit')->name('user.admin.role.verifyFieldsEdit');
    Route::post('/bulkEdit', 'RoleController@bulkEdit')->name('user.admin.role.bulkEdit');
    Route::post('/save_permissions', 'RoleController@save_permissions')->name('user.admin.role.save_permissions');
    Route::get('/getForSelect2','RoleController@getForSelect2')->name('user.admin.role.getForSelect2');

});

Route::group(['prefix' => 'verification'], function () {
    Route::get('/', 'VerificationController@index')->name('user.admin.verification.index');
    Route::get('detail/{id}', 'VerificationController@detail')->name('user.admin.verification.detail');
    Route::post('store/{id}', 'VerificationController@store')->name('user.admin.verification.store');
    Route::post('/bulkEdit', 'VerificationController@bulkEdit')->name('user.admin.verification.bulkEdit');
});


Route::group(['prefix'=>'wallet'],function (){
    Route::get('/add-credit/{id}','WalletController@addCredit')->name('user.admin.wallet.addCredit');
    Route::post('/add-credit/{id}','WalletController@store')->name('user.admin.wallet.store');
    Route::get('/report','WalletController@report')->name('user.admin.wallet.report');
    Route::post('/reportBulkEdit','WalletController@reportBulkEdit')->name('user.admin.wallet.reportBulkEdit');

});


Route::group(['prefix' => 'subscriber'], function () {
    Route::get('/', 'SubscriberController@index')->name('user.admin.subscriber.index');
    Route::get('edit/{id}', 'SubscriberController@edit')->name('user.admin.subscriber.edit');
    Route::post('store', 'SubscriberController@store')->name('user.admin.subscriber.store');
    Route::post('/bulkEdit', 'SubscriberController@bulkEdit')->name('user.admin.subscriber.bulkEdit');
    Route::get('export', 'SubscriberController@export')->name('user.admin.subscriber.export');
});

Route::get('/export', 'UserController@export')->name('user.admin.export');


Route::group(['prefix'=>'plan'],function (){
    Route::get('/','PlanController@index')->name('user.admin.plan.index');
    Route::get('/edit/{id}','PlanController@edit')->name('user.admin.plan.edit');
    Route::post('/store/{id}','PlanController@store')->name('user.admin.plan.store');
    Route::post('/bulkEdit','PlanController@bulkEdit')->name('user.admin.plan.bulkEdit');
    Route::get('/getForSelect2','PlanController@getForSelect2')->name('user.admin.plan.getForSelect2');
});

Route::group(['prefix'=>'plan-request'],function (){
    Route::get('/','PlanRequestController@index')->name('user.admin.plan_request.index');
    Route::post('/bulkEdit','PlanRequestController@bulkEdit')->name('user.admin.plan_request.bulkEdit');
});
Route::group(['prefix'=>'plan-report'],function (){
    Route::get('/','PlanReportController@index')->name('user.admin.plan_report.index');
    Route::post('/bulkEdit','PlanReportController@bulkEdit')->name('user.admin.plan_report.bulkEdit');
});
