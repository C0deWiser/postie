<?php

namespace Codewiser\Postie\Collections;

use Codewiser\Postie\Channel;
use Codewiser\Postie\Models\Subscription;
use Illuminate\Support\Collection;

class ChannelCollection extends Collection
{
    /**
     * Get resolved channels array with status by notifiable subscription.
     *
     * @param mixed $notifiable
     * @param Subscription|null $subscription
     */
    public function getResolvedByNotifiableSubscription($notifiable, Subscription $subscription = null): array
    {
        return $this
            ->map(function (Channel $definition) use ($notifiable, $subscription) {
                $defaults = $definition->toArray();

                // If record has channel...
                $userPreferences = $subscription && isset($subscription->channels[$definition->getName()])
                    ? $subscription->channels[$definition->getName()]
                    : null;

                $defaults['status'] = $definition->getStatus($notifiable, $userPreferences);
                $defaults['available'] = (bool)$notifiable->routeNotificationFor($definition->getName());
                return $defaults;
            })
            ->toArray();
    }
}
