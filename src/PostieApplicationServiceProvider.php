<?php

namespace Codewiser\Postie;

use Codewiser\Postie\Attributes\Channel as ChannelAttribute;
use Codewiser\Postie\Attributes\Description;
use Codewiser\Postie\Attributes\Group as GroupAttribute;
use Codewiser\Postie\Attributes\Preview;
use Codewiser\Postie\Attributes\Subject;
use Illuminate\Notifications\Notification;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Finder\Finder;

abstract class PostieApplicationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Group::useService(fn() => app(PostieService::class));
        Subscription::useService(fn() => app(PostieService::class));

        PostieService::$channels = fn() => $this->channels();

        PostieService::$groups = fn() => $this->groups();

        PostieService::$subscriptions = function () {
            $definitions = [];

            foreach ($this->notifications() as $notification) {
                if ($notification instanceof Group) {
                    foreach ($notification->getSubscriptions() as $subscription) {
                        $definitions[] = $subscription->group($notification);
                    }
                } elseif ($notification instanceof Subscription) {
                    $definitions[] = $notification;
                } else {
                    // A notification class name is wrapped into a Subscription.
                    $definitions[] = Subscription::to($notification);
                }
            }

            $registered = array_map(
                fn(Subscription $subscription) => $subscription->getNotification(),
                $definitions
            );

            // Discover notifications that apply Postie attributes.
            foreach ($this->discoverNotifications() as $notification) {
                if (! in_array($notification, $registered, true)) {
                    $definitions[] = Subscription::to($notification);
                }
            }

            return $definitions;
        };
    }

    /**
     * Discover notifications that apply any of the Postie attributes.
     *
     * @return array<int, class-string<Notification>>
     */
    protected function discoverNotifications(): array
    {
        $notifications = [];

        foreach ($this->notificationPaths() as $path) {
            if (! is_dir($path)) {
                continue;
            }

            foreach ((new Finder)->files()->in($path)->name('*.php') as $file) {
                $class = $this->resolveClassName($file->getPathname());

                if ($class && ! in_array($class, $notifications, true) && $this->usesPostie($class)) {
                    $notifications[] = $class;
                }
            }
        }

        return $notifications;
    }

    /**
     * Get directories to look for notification classes.
     *
     * @return array<int, string>
     */
    protected function notificationPaths(): array
    {
        $paths = config('postie.notifications_path');

        if (is_string($paths)) {
            $paths = explode(',', $paths);
        }

        return array_values(array_filter(
            array_map('trim', (array) $paths),
            fn($path) => $path !== ''
        ));
    }

    /**
     * Get class name declared in a PHP file.
     *
     * @return null|class-string
     */
    protected function resolveClassName(string $file): ?string
    {
        $source = file_get_contents($file);

        preg_match('/^\s*namespace\s+([^\s;]+)\s*;/m', $source, $namespace);
        preg_match('/^\s*(?:final\s+|abstract\s+|readonly\s+)*class\s+(\w+)/m', $source, $class);

        if (! $class) {
            return null;
        }

        $class = ($namespace[1] ?? null)
            ? $namespace[1].'\\'.$class[1]
            : $class[1];

        return class_exists($class) ? $class : null;
    }

    /**
     * Does the class apply any of the Postie attributes?
     *
     * @param  class-string<Notification>  $class
     */
    protected function usesPostie(string $class): bool
    {
        if (! is_subclass_of($class, Notification::class)) {
            return false;
        }

        $reflection = new \ReflectionClass($class);

        if ($reflection->getAttributes(ChannelAttribute::class)) {
            return true;
        }

        return false;
    }

    /**
     * Return an array of Subscriptions or Groups of Subscriptions.
     *
     * A notification class name may be passed as is: it will be wrapped
     * into a Subscription.
     *
     * @return array<int, Subscription|Group|class-string<Notification>>
     */
    abstract public function notifications(): array;

    /**
     * Return an array of Channel definitions.
     *
     * @return array<int, Channel>
     */
    public function channels(): array
    {
        return [];
    }

    /**
     * Return an array of Group definitions.
     *
     * @return array<int, Group>
     */
    public function groups(): array
    {
        return [];
    }
}
