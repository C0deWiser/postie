<?php

namespace Codewiser\Postie\Http\Controllers;

use Codewiser\Postie\Contracts\Postie;
use Codewiser\Postie\Http\Requests\SubscriptionToggleRequest;
use Codewiser\Postie\Http\Resources\SubscriptionResource;
use Codewiser\Postie\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * Список оповещений пользователя
     * @param Request $request
     * @param Postie $postie
     * @return JsonResponse
     */
    public function index(Request $request, Postie $postie)
    {
        $notificationDefinitions = $postie->getUserNotifications($request->user()->getKey());
        return response()->json([
            'notification_definitions' => $notificationDefinitions,
        ]);
    }

    /**
     * Изменение статуса канала оповещения пользователя
     *
     * @param SubscriptionToggleRequest $request
     * @param Postie $postie
     * @return SubscriptionResource
     */
    public function toggle(SubscriptionToggleRequest $request, Postie $postie)
    {
        $subscription = $postie->toggleUserNotificationChannels($request->user()->getKey(), $request->notification, $request->channels);
        return SubscriptionResource::make($subscription);
    }
}
