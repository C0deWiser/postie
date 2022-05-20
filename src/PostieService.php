<?php

namespace Codewiser\Postie;

use Codewiser\Postie\Contracts\Channelizationable;
use Codewiser\Postie\Contracts\Postie;
use Codewiser\Postie\Contracts\PostieAssets;
use Codewiser\Postie\Models\Contracts\Subscriptionable;
use Illuminate\Support\Collection;
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

}
