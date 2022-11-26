<?php

use Codewiser\Postie\Http\Controllers\HomeController;
use Codewiser\Postie\Http\Controllers\PreviewingController;
use Codewiser\Postie\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    Route::post('subscriptions/toggle', [SubscriptionController::class, 'toggle'])->name('subscriptions.toggle');
    Route::apiResource('subscriptions', SubscriptionController::class)->only(['index']);
});

Route::get('preview/{channel}/{notification}', PreviewingController::class)
    ->name('preview');

// Catch-all Route...
Route::get('/{view?}', [HomeController::class, 'index'])
    ->where('view', '(.*)')->name('index');
