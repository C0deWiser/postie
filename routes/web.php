<?php

use Codewiser\Postie\Http\Controllers\DocsController;
use Codewiser\Postie\Http\Controllers\HomeController;
use Codewiser\Postie\Http\Controllers\PreviewingController;
use Codewiser\Postie\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    Route::post('subscriptions/toggle', [SubscriptionController::class, 'toggle'])->name('subscriptions.toggle');
    Route::get('subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
});

Route::get('api', [DocsController::class, 'index'])->name('api.docs');
Route::get('api/openapi.yaml', [DocsController::class, 'openapi'])->name('api.openapi');

Route::get('preview/{channel}/{notification}', PreviewingController::class)
    ->name('preview');

// Catch-all Route...
Route::get('/{view?}', [HomeController::class, 'index'])
    ->where('view', '(.*)')->name('index');
