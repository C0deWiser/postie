<?php

use Codewiser\Postie\Http\Controllers\Api\AudiencesController;
use Codewiser\Postie\Http\Controllers\Api\ChannelsController;
use Codewiser\Postie\Http\Controllers\Api\GroupsController;
use Codewiser\Postie\Http\Controllers\Api\PreferencesController;
use Codewiser\Postie\Http\Controllers\Api\SubscriptionsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Postie CRUD API
|--------------------------------------------------------------------------
|
| Read models for a custom dashboard: channel, group and audience
| definitions, a single subscription, and user channel preferences.
|
*/

Route::prefix('api')->group(function () {
    Route::apiResource('channels', ChannelsController::class)->only(['index', 'show']);
    Route::apiResource('groups', GroupsController::class)->only(['index', 'show']);
    Route::apiResource('audiences', AudiencesController::class)->only(['index', 'show']);

    Route::apiResource('notifications', SubscriptionsController::class)->only(['index', 'show']);

    Route::apiResource('notifications.channels', PreferencesController::class)
        ->only(['update', 'destroy'])
        ->names([
            'update'  => 'notifications.channels.subscribe',
            'destroy' => 'notifications.channels.unsubscribe',
        ]);
});