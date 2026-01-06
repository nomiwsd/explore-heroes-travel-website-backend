<?php
use Illuminate\Support\Facades\Route;

Route::match(['get','post'],'/','ReviewController@index')->name('review.admin.index');
Route::get('/edit/{id}','ReviewController@edit')->name('review.admin.edit');
Route::post('/store/{id?}','ReviewController@store')->name('review.admin.store');
Route::post('/bulkEdit','ReviewController@bulkEdit')->name('review.admin.bulkEdit');
