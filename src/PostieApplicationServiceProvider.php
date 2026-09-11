<?php

namespace Codewiser\Postie;

use Illuminate\Support\ServiceProvider;

abstract class PostieApplicationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Group::useService(fn() => app(PostieService::class));
        Subscription::useService(fn() => app(PostieService::class));

        PostieService::$channels = function () {
            return $this->channels();
        };

        PostieService::$subscriptions = function () {
            $definitions = [];

            foreach ($this->notifications() as $notification) {
                if ($notification instanceof Group) {
                    foreach ($notification->getSubscriptions() as $subscription) {
                        $definitions[] = $subscription->group($notification);
                    }
                } else {
                    $definitions[] = $notification;
                }
            }

            return $definitions;
        };
    }

    /**
     * Return an array of Subscriptions or Groups of Subscriptions
     *
     * @return array<int, Subscription|Group>
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
}
