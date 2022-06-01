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
     * User notifications list.
     */
    public function index(Request $request, Postie $postie)
    {
        return response()->json([
            'notification_definitions' => $postie->getUserNotifications($request->user()),
        ]);
    }

    /**
     * Change user preferences.
     */
    public function toggle(SubscriptionToggleRequest $request, Postie $postie)
    {
        $subscription = $postie->toggleUserNotificationChannels(
            $request->user(),
            $request->notification,
            $request->channels
        );

        return SubscriptionResource::make($subscription);
    }
}
