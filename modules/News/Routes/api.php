<?php

use Illuminate\Support\Facades\Route;
use Modules\Api\Controllers\NewsController;

Route::prefix('news')->group(function() {
    Route::get('/', [NewsController::class, 'search'])->name('api.news.search');
    Route::get('/featured', [NewsController::class, 'featured'])->name('api.news.featured');
    Route::get('/categories', [NewsController::class, 'category'])->name('api.news.categories');
    Route::get('/locations', [NewsController::class, 'locations'])->name('api.news.locations');
    Route::get('/all-locations', [NewsController::class, 'allLocations'])->name('api.news.all-locations');
    Route::get('/{id}', [NewsController::class, 'detail'])->name('api.news.detail');
});