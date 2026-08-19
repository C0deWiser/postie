<?php

namespace Codewiser\Postie;

use Codewiser\Postie\Collections\Groups;
use Codewiser\Postie\Collections\Subscriptions;
use Codewiser\Postie\Events\UserSubscribe;
use Codewiser\Postie\Events\UserUnsubscribe;
use Codewiser\Postie\Models\Preference;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\ItemNotFoundException;
use Illuminate\Support\MultipleItemsFoundException;

class PostieService
{
    /**
     * @var callable
     */
    public static $definitions;

    public function assetsAreCurrent(): bool
    {
        $publishedPath = public_path('vendor/postie/mix-manifest.json');

        if (! File::exists($publishedPath)) {
            throw new \RuntimeException('Postie assets are not published. Please run: php artisan postie:publish');
        }

        return File::get($publishedPath) === File::get(__DIR__.'/../public/mix-manifest.json');
    }

    public function scriptVariables(): array
    {
        return [
            'path' => config('postie.path'),
        ];
    }

    /**
     * Get defined subscriptions (all list or for exact notifiable).
     */
    public function getSubscriptions(Model $notifiable = null): Subscriptions
    {
        $subscriptions = new Subscriptions(call_user_func(self::$definitions));

        return $notifiable
            ? $subscriptions->for($notifiable)
            : $subscriptions;
    }

    /**
     * Get all defined groups for a given notifiable.
     */
    public function getGroups(Model $notifiable): Groups
    {
        return $this->getSubscriptions($notifiable)->groups();
    }

    /**
     * Get notification channels for the given notifiable.
     *
     * @param  class-string<Notification>  $notification
     * @param  object  $notifiable
     *
     * @return array<int, string>
     */
    public function via(string $notification, object $notifiable): array
    {
        $subscription = $this->getSubscriptions()->find($notification);

        if ($notifiable instanceof AnonymousNotifiable) {
            return array_intersect(
                array_keys($notifiable->routes),
                $subscription->getChannels()->names()
            );
        }

        /** @var Preference $preference */
        $preference = ($notifiable instanceof Model)
            ? Preference::for($notifiable, $notification)->first()
            : null;

        // Merge pre-defined preferences with user preferences
        $channels = $subscription
            ->getChannels()
            ->getPreferences($preference?->channels ?? []);

        // Get names of enabled channels
        $channels = array_keys(array_filter($channels));

        // Check if route available for the notifiable.
        // Broadcast doesn't require routing...
        return array_filter($channels,
            fn($channel) => ($channel == 'broadcast') || $notifiable->routeNotificationFor($channel)
        );
    }

    /**
     * Toggles user preferences.
     *
     * @param  class-string<Notification>  $notification
     * @param  array<string, bool>  $prefs
     * @param  null|string  $variety
     */
    public function toggleUserPreferences(
        Model $notifiable,
        string $notification,
        array $prefs,
        ?string $variety
    ): Preference {
        $subscription = $this->getSubscriptions()->find($notification);

        // Filter user preferences
        // Left only channels with state differs from predefined
        $prefs = array_filter(
            $subscription->getChannels()->getPreferences($prefs),
            fn(bool $status, string $channel) => $subscription->getChannels()->find($channel)->getDefault() !== $status,
            ARRAY_FILTER_USE_BOTH
        );

        /** @var Preference $preference */
        $preference = Preference::for($notifiable, $notification)->first();

        if (! $preference) {
            // Create preferences
            $preference = new Preference;
            $preference->notifiable()->associate($notifiable);
            $preference->notification = $notification;
        }

        if ($prefs) {
            $preference->channels = $prefs;
            $preference->save();
        } elseif ($preference->exists) {
            $preference->delete();
        }

        foreach ($prefs as $channel => $subscribed) {
            $subscribed
                ? event(new UserSubscribe($notifiable, $notification, $channel))
                : event(new UserUnsubscribe($notifiable, $notification, $channel));
        }

        return $preference;
    }

    /**
     * @deprecated
     */
    public function send(Notification $notification, $callback = null)
    {
        $audience = null;

        try {
            $definition = $this->getSubscriptions()->find(get_class($notification));

            if ($builder = $definition->getAudience()) {
                $audience = is_callable($callback)
                    // Modify predefined audience builder with a callback
                    ? call_user_func($callback, $builder, $notification)
                    // Use predefined audience builder
                    : $builder;
            }

        } catch (ItemNotFoundException|MultipleItemsFoundException $exception) {
            // Fallback

            $audience = is_callable($callback)
                // Get notifiable(s) (or its builder) from a callback
                ? call_user_func($callback, $notification)
                // Get notifiable(s) from argument
                : $callback;
        }

        if ($audience instanceof Builder) {
            $audience->chunk(100,
                fn(Collection $audience) => NotificationFacade::send($audience, $notification)
            );
        } elseif ($audience && method_exists($audience, 'notify')) {
            $audience->notify($notification);
        }

        return $audience;
    }
}
