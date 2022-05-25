<?php

namespace Codewiser\Postie\Collections;

use Codewiser\Postie\Models\Subscription;
use Illuminate\Database\Eloquent\Collection;

class SubscriptionCollection extends Collection
{
    /**
     * Find first subscription by notification class name
     */
    public function firstByNotification(string $notification):?Subscription
    {
        return $this->first(function (Subscription $subscription) use ($notification) {
            return $subscription->notification === $notification;
        });
    }
}