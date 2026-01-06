<?php
use Illuminate\Support\Facades\Route;

Route::get('/','ContactController@index')->name('contact.admin.index');
Route::get('/{id}','ContactController@show')->name('contact.admin.show');
Route::post('/{id}','ContactController@update')->name('contact.admin.update');
Route::post('/bulkEdit','ContactController@bulkEdit')->name('contact.admin.bulkEdit');
Route::get('/export', 'ContactController@exportCsv')->name('contact.admin.export');

Route::get('getForSelect2','ContactController@getForSelect2')->name('contact.admin.getForSelect2');
