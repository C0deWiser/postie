<?php

use Codewiser\Postie\Http\Controllers\HomeController;
use Codewiser\Postie\Http\Controllers\SubsController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    Route::apiResource('subs', SubsController::class)->only(['index', 'update', 'store']);
});

// Catch-all Route...
Route::get('/{view?}', [HomeController::class, 'index'])->where('view', '(.*)')->name('index');
