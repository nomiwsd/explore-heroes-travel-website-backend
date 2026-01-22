<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'newsletter'], function () {
    Route::post('subscribe', 'NewsletterController@subscribe');
});

Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::group(['prefix' => 'admin/newsletter'], function () {
        Route::get('subscribers', 'NewsletterController@index');
    });
});
