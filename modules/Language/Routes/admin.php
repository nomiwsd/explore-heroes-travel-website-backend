<?php
use Illuminate\Support\Facades\Route;

Route::match(['get', 'post'],'/','LanguageController@index')->name('language.admin.index');
Route::match(['get', 'post'],'edit/{id}','LanguageController@edit')->name('language.admin.edit');
Route::post('bulkEdit','LanguageController@bulkEdit')->name('language.admin.bulkEdit');


Route::group(['prefix'=>'translation'],function (){
    Route::get('/','TranslationsController@index')->name('language.admin.translations.index');
    Route::get('detail/{id}','TranslationsController@detail')->name('language.admin.translations.detail');
    Route::post('store/{id}','TranslationsController@store')->name('language.admin.translations.store');
    Route::get('build/{id}','TranslationsController@build')->name('language.admin.translations.build');
    Route::get('loadTranslateJson','TranslationsController@loadTranslateJson')->name('language.admin.translations.loadTranslateJson');
    Route::get('loadStrings','TranslationsController@loadStrings')->name('language.admin.translations,loadStrings');
    Route::get('findTranslations','TranslationsController@findTranslations')->name('language.admin.translations.findTranslations');
});

// API Routes for translations (JSON responses)
Route::group(['prefix' => 'translations'], function () {
    Route::get('/{locale}', 'TranslationsController@getTranslationsApi')->name('language.admin.translations.api.list');
    Route::post('/{locale}/save', 'TranslationsController@saveTranslationsApi')->name('language.admin.translations.api.save');
    Route::post('/{locale}/build', 'TranslationsController@buildTranslationsApi')->name('language.admin.translations.api.build');
    Route::get('/{locale}/stats', 'TranslationsController@getStatsApi')->name('language.admin.translations.api.stats');
    Route::get('/{locale}/export', 'TranslationsController@exportTranslations')->name('language.admin.translations.api.export');
    Route::post('/scan', 'TranslationsController@scanForStringsApi')->name('language.admin.translations.api.scan');
});

