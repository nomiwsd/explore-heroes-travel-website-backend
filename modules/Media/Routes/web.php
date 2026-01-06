<?php
use Illuminate\Support\Facades\Route;
//
Route::get('media/private/view','MediaController@privateFileView')->middleware('auth')->name('media.private.view');
Route::post('media/edit_image','MediaController@editImage')->name('media.edit.image');

// Media
Route::group(['prefix'=>'media'],function(){
    Route::get('/preview/{id}/{size?}','\Modules\Media\Controllers\MediaController@preview');//
    Route::post('/private/store','MediaController@privateFileStore')->name('media.private.store');//
});

// Admin/Module routes with API support
Route::group(['middleware' => ['web', 'api_dashboard'],'prefix' => config('admin.admin_route_prefix')],function(){
    Route::post('/module/media/store', '\Modules\Media\Admin\MediaController@store')->name('media.store');
    Route::post('/module/media/getLists','\Modules\Media\Admin\MediaController@getLists');
    Route::post('/module/media/removeFiles','\Modules\Media\Admin\MediaController@removeFiles');
    Route::post('/media/folder','FolderController@index')->name('media.folder.index');
});

// Module routes without admin prefix (for frontend API calls)
Route::group(['middleware' => ['web', 'api_dashboard'],'prefix' => 'module/media'],function(){
    Route::get('/','MediaController@index')->name('module.media.index');
    Route::post('/store', '\Modules\Media\Admin\MediaController@store')->name('module.media.store');
    Route::post('/getLists','\Modules\Media\Admin\MediaController@getLists')->name('module.media.getLists');
    Route::post('/removeFiles','\Modules\Media\Admin\MediaController@removeFiles')->name('module.media.removeFiles');
    Route::get('/{id}','\Modules\Media\Admin\MediaController@edit')->name('module.media.edit');
    Route::post('/{id}/update','\Modules\Media\Admin\MediaController@update')->name('module.media.update');
    Route::post('/edit_image','\Modules\Media\Admin\MediaController@editImage')->name('module.media.edit.image');
});

// Media folder routes with API support
Route::group(['middleware' => ['web', 'api_dashboard'],'prefix' => 'media'],function(){
    Route::get('/folder','FolderController@index')->name('media.folder.get');
    Route::post('/folder','FolderController@index');
    Route::post('/folder/store','FolderController@store')->name('media.folder.store');
    Route::post('/folder/delete','FolderController@delete')->name('media.folder.delete');
});
