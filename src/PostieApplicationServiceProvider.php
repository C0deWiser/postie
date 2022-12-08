<?php

namespace Codewiser\Postie;

use Illuminate\Support\ServiceProvider;

abstract class PostieApplicationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $definitions = [];

        foreach ($this->notifications() as $notification) {
            if ($notification instanceof Group) {
                foreach ($notification->getSubscriptions() as $subscription) {
                    $definitions[] = $subscription
                        ->group($notification);
                }
            } else {
                $definitions[] = $notification;
            }
        }

        PostieService::$notifications = $definitions;
    }

    /**
     * Return an array of Subscriptions or Groups of Subscriptions
     *
     * @return array<Subscription, Group>
     */
    abstract public function notifications(): array;
}
