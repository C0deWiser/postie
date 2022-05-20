<?php

namespace Codewiser\Postie\Http\Controllers;

use Codewiser\Postie\Contracts\Postie;
use Codewiser\Postie\Http\Requests\SubscriptionStoreRequest;
use Codewiser\Postie\Models\Subscription;
use Codewiser\Postie\NotificationDefinition;
use Illuminate\Http\Request;

class SubsController extends Controller
{
    public function index(Request $request, Postie $postie)
    {
        // Отбираем определения оповещений, в которых есть пользователь
        $userId = $request->user()->getKey();
        $notificationDefinitions = $postie
            ->notificationDefinitions()
            ->filter(function (NotificationDefinition $notificationDefinition) use ($userId) {
                $existedUserInQuery = $notificationDefinition->getAudienceBuilder()->find($userId);
                return $existedUserInQuery ? true : false;
            });

        // Получаем массив свойств notification из массива определений
        $userNotifications = $notificationDefinitions->map(function (NotificationDefinition $notificationDefinition) {
            return $notificationDefinition->getNotification();
        })->toArray();

        $userSubscriptions = Subscription::query()
            ->where('user_id', $userId)
            ->whereIn('notification', $userNotifications)
            ->get();

        return [
            'notification_definitions' => $notificationDefinitions->toArray(),
            'user_id' => $userId,
            'user_subscriptions' => $userSubscriptions->toArray(),
        ];
    }

    public function store(SubscriptionStoreRequest $request)
    {
        Subscription::query()->create($request->validated());
        return response()->noContent(201);
    }
    
    public function update(Request $request)
    {
        dd($request->all());
    }
}
