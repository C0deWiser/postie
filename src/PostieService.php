<?php

namespace Codewiser\Postie;

use Closure;
use Codewiser\Postie\Collections\NotificationCollection;
use Codewiser\Postie\Contracts\Postie;
use Codewiser\Postie\Contracts\PostieAssets;
use Codewiser\Postie\Events\UserSubscribe;
use Codewiser\Postie\Events\UserUnsubscribe;
use Codewiser\Postie\Models\Subscription;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ItemNotFoundException;
use Illuminate\Support\MultipleItemsFoundException;

class PostieService implements Postie
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

    public function getUserNotifications($notifiable): array
    {
        $userNotificationDefinitions = $this->getNotifications()->for($notifiable);

        return $userNotificationDefinitions->buildUserNotificationsWithChannelStatuses($notifiable);
    }

    public function toggleUserNotificationChannels($notifiable, string $notification, array $channels): Subscription
    {
        /** @var Subscription $subscription */
        $subscription = Subscription::for($notifiable, $notification)->first();

        if ($subscription) {
            // Update preferences
            $subscription->channels = $this
                ->getNotifications()
                ->find($notification)
                ->getUserChannels(array_merge($subscription->channels, $channels));
        } else {
            // Create preferences
            $subscription = new Subscription;
            $subscription->notifiable()->associate($notifiable);
            $subscription->notification = $notification;
            $subscription->channels = $this
                ->getNotifications()
                ->find($notification)
                ->getUserChannels($channels);
        }
        $subscription->save();

        foreach ($channels as $channel => $subscribed) {
            if ($subscribed) {
                event(new UserSubscribe($notifiable, $notification, $channel));
            } else {
                event(new UserUnsubscribe($notifiable, $notification, $channel));
            }
        }

        return $subscription;
    }

    public function send(Notification $notification, $closure = null)
    {
        try {
            $definition = $this->getNotifications()->find(get_class($notification));

            $audience = (is_callable($closure) || $closure instanceof Closure)
                // Modify predefined audience builder with callback
                ? call_user_func($closure, $definition->getAudienceBuilder())
                // Use predefined audience builder
                : $definition->getAudienceBuilder();

        } catch (ItemNotFoundException|MultipleItemsFoundException $exception) {
            // Fallback

            $audience = (is_callable($closure) || $closure instanceof Closure)
                // Get notifiable(s) (or its builder) from callback
                ? call_user_func($closure)
                // Get notifiable(s) from argument
                : $closure;
        }

        if ($audience instanceof \Illuminate\Contracts\Database\Query\Builder) {
            $audience = $audience->get();
        }

        if ($audience) {
            \Illuminate\Support\Facades\Notification::send($audience, $notification);
        }
    }
}
