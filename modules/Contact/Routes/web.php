<?php
use Illuminate\Support\Facades\Route;
//Contact
Route::get('/contact','ContactController@index')->name("contact.index");
Route::post('/contact/store','ContactController@store')->name("contact.store");

Route::get('/tailor-made-tour','ContactController@indexTailorMadeTour')->name("tailor-made-tour.indexTailorMadeTour");
Route::post('/tailor-made-tour/store','ContactController@storeTailorMadeTour')->name("tailor-made-tour.storeTailorMadeTour");

Route::get('/clear', function () {
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('view:clear');
    Artisan::call('route:clear');
    return "All clear";
});

Route::get('/optimize', function () {
    Artisan::call('optimize');
    return "Optimized";
});
