<?php

namespace Codewiser\Postie;

use Codewiser\Postie\Contracts\Channelizationable;
use Codewiser\Postie\Contracts\Postie;
use Codewiser\Postie\Contracts\PostieAssets;
use Codewiser\Postie\Models\Contracts\Subscriptionable;
use Codewiser\Postie\Models\Subscription;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class PostieService implements PostieAssets, Postie
{
    /**
     * @var array|NotificationDefinition[]
     */
    public static array $notifications = [];

    public function assetsAreCurrent(): bool
    {
        $publishedPath = public_path('vendor/postie/mix-manifest.json');

        if (!File::exists($publishedPath)) {
            throw new \RuntimeException('Postie assets are not published. Please run: php artisan postie:publish');
        }

        return File::get($publishedPath) === File::get(__DIR__ . '/../public/mix-manifest.json');
    }

    public function scriptVariables(): array
    {
        return [
            'path' => config('postie.path'),
        ];
    }

    public function notificationDefinitions(): Collection
    {
        return collect(self::$notifications);
    }

    public function findNotificationDefinitionByNotification(string $notification): ?NotificationDefinition
    {
        return $this->notificationDefinitions()->first(function (NotificationDefinition $notificationDefinition, $index) use ($notification) {
            return $notificationDefinition->getNotification() === $notification;
        });
    }

    public function via(string $notification, $notifiable): array
    {
        // Пытаемся найти определнное оповещение
        $notificationDefinition = $this->findNotificationDefinitionByNotification($notification);

        if (!$notificationDefinition) {
            return [];
        }

        $subscription = $notifiable->subscriptions()->where('notification', $notification)->first();
        $userChannels = $subscription ? $subscription->channels : [];
        $actualChannels = $notificationDefinition->getActualChannelsWithStatus($userChannels);

        // Обновляем актуальный список каналов подписки пользователя, если это необходимо
        if ($subscription) {
            if (
                count(array_diff_assoc($subscription->channels, $actualChannels)) ||
                count($subscription->channels) !== count($actualChannels)
            ) {
                $subscription->channels = $actualChannels;
                $subscription->save();
            }
        }

        $actualChannels = array_filter($actualChannels, function ($status) {
            return $status;
        }, ARRAY_FILTER_USE_BOTH);
        $activeChannels = array_keys($actualChannels);

        return $activeChannels;
    }

    public function getNotificationNames(): array
    {
        return $this
            ->notificationDefinitions()
            ->map(function (NotificationDefinition $notificationDefinition) {
                return $notificationDefinition->getNotification();
            })
            ->toArray();
    }

    public function getUserNotifications(int $userId): array
    {
        // Отбираем определения оповещений, в которых есть пользователь
        $notificationDefinitions = $this
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
            ->get()
            ->keyBy('notification');

        $result = [];
        foreach ($notificationDefinitions as $notificationDefinition) {
            $row = [];
            $row['notification'] = $notificationDefinition->getNotification();
            $row['title'] = $notificationDefinition->getTitle();

            $channels = [];
            foreach ($notificationDefinition->getChannels() as $channelDefinition) {
                $currentChannel = $channelDefinition->toArray();

                $userChannelStatus = null;

                if (isset($userSubscriptions[$notificationDefinition->getNotification()])) {
                    // Если у пользователя есть записи о подписке на данное оповещение
                    $userSubscription = $userSubscriptions[$notificationDefinition->getNotification()];
                    $currentChannelName = $channelDefinition->getName();

                    if (isset($userSubscription->channels[$currentChannelName])) {
                        // Если в данной записи определен текущий канал оповещения
                        $userChannelStatus = $userSubscription->channels[$currentChannelName];
                    }
                }

                $currentChannel['status'] = $channelDefinition->getStatus($userChannelStatus);
                $channels[] = $currentChannel;
            }

            $row['channels'] = $channels;

            $result[] = $row;
        }

        return $result;
    }

    public function toggleUserNotificationChannels(int $userId, string $notification, array $channels): Subscription
    {
        $subscription = Subscription::query()
            ->where('notification', $notification)
            ->where('user_id', $userId)
            ->first();

        if ($subscription) {
            // Обновление записи о подписке
            $data['channels'] = $this
                ->findNotificationDefinitionByNotification($notification)
                ->getActualChannelsWithStatus(array_merge($subscription->channels, $channels));
            $subscription->update($data);
        } else {
            // Создание записи о подписке
            $data = [
                'user_id' => $userId,
                'notification' => $notification,
                'channels' => $this
                    ->findNotificationDefinitionByNotification($notification)
                    ->getActualChannelsWithStatus($channels),
            ];
            $subscription = Subscription::query()->create($data);
        }

        return $subscription;
    }
}
