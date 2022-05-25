<?php

namespace Codewiser\Postie;

use Codewiser\Postie\Collections\NotificationCollection;
use Codewiser\Postie\Contracts\Postie;
use Codewiser\Postie\Contracts\PostieAssets;
use Codewiser\Postie\Models\Subscription;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class PostieService implements PostieAssets, Postie
{
    /**
     * @var array<NotificationDefinition>
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

    public function getNotifications(): NotificationCollection
    {
        return NotificationCollection::make(self::$notifications);
    }

    /**
     * Get notification channels for the given notifiable.
     *
     * @param string $notification
     * @param Model|Notifiable $notifiable
     * @return array
     */
    public function via(string $notification, $notifiable): array
    {
        $notificationDefinition = $this->getNotifications()->find($notification);

        if (!$notificationDefinition) {
            return [];
        }

        /** @var Subscription $subscription */
        $subscription = Subscription::for($notifiable, $notification)->first();
        $userChannels = $subscription ? $subscription->channels : [];
        $actualChannels = $notificationDefinition->getUserChannels($userChannels);

        // Actualize user preferences...
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

        // Check if route available for the notifiable.
        return array_filter($activeChannels, function ($channel) use ($notifiable) {
            return (bool)$notifiable->routeNotificationFor($channel);
        });
    }

    public function getUserNotifications(Model $notifiable): array
    {
        // Filter notifications relevant to notifiable
        $notificationDefinitions = $this->getNotifications()->for($notifiable);

        // Get notifications properties
        $userNotifications = $notificationDefinitions
            ->map(function (NotificationDefinition $notificationDefinition) {
                return $notificationDefinition->getClassName();
            })
            ->toArray();

        $subscriptions = Subscription::for($notifiable, $userNotifications)->get()
            ->keyBy('notification');

        $result = [];

        /** @var NotificationDefinition $notificationDefinition */
        foreach ($notificationDefinitions as $notificationDefinition) {
            $row = [];
            $row['notification'] = $notificationDefinition->getClassName();
            $row['title'] = $notificationDefinition->getTitle();

            $channels = [];
            /** @var ChannelDefinition $channelDefinition */
            foreach ($notificationDefinition->getChannels() as $channelDefinition) {
                $currentChannel = $channelDefinition->toArray();

                $userChannelStatus = null;

                if (isset($subscriptions[$notificationDefinition->getClassName()])) {
                    // Если у пользователя есть записи о подписке на данное оповещение
                    $userSubscription = $subscriptions[$notificationDefinition->getClassName()];
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

    public function toggleUserNotificationChannels(Model $notifiable, string $notification, array $channels): Subscription
    {
        /** @var Subscription $subscription */
        $subscription = Subscription::for($notifiable, $notification)->first();

        if ($subscription) {
            // Update preferences
            $subscription->channels = $this
                ->getNotifications()->find($notification)
                // todo null?
                ->getUserChannels(array_merge($subscription->channels, $channels));
        } else {
            // Create preferences
            $subscription = new Subscription;
            $subscription->morphTo()->associate($notifiable);
            $subscription->notification = $notification;
            $subscription->channels = $this
                ->getNotifications()->find($notification)
                // todo null?
                ->getUserChannels($channels);
        }
        $subscription->save();

        return $subscription;
    }
}
