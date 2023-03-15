<?php

namespace Codewiser\Postie\Collections;

use Codewiser\Postie\Channel;
use Codewiser\Postie\Models\Subscription;
use Illuminate\Support\Collection;
use Illuminate\Support\ItemNotFoundException;
use Illuminate\Support\MultipleItemsFoundException;

class ChannelCollection extends Collection
{
    /**
     * Find channel definition by its name.
     *
     * @throws ItemNotFoundException
     * @throws MultipleItemsFoundException
     */
    public function find(string $channel): Channel
    {
        return $this
            ->sole(
                fn(Channel $definition) => $definition->getName() === $channel
            );
    }

    public function names(): array
    {
        return $this
            ->map(
                fn(Channel $channel) => $channel->getName()
            )
            ->toArray();
    }

    /**
     * Get resolved channels array with status by notifiable subscription.
     */
    public function getResolvedByNotifiableSubscription($notifiable, \Codewiser\Postie\Subscription $notification, Subscription $subscription = null): array
    {
        return $this
            ->map(function (Channel $definition) use ($notifiable, $notification, $subscription) {
                $defaults = $definition->toArray();

                // If record has channel...
                $userPreferences = $subscription && isset($subscription->channels[$definition->getName()])
                    ? $subscription->channels[$definition->getName()]
                    : null;

                $defaults['status'] = $definition->getStatus($notifiable, $userPreferences);
                $defaults['available'] = $definition->getName() == 'broadcast' || (bool)$notifiable->routeNotificationFor($definition->getName());

                if ($notification->hasPreview()) {
                    $defaults['previewing'] = route('postie.preview', [
                        'channel' => $definition->getName(),
                        'notification' => $notification->getClassName()
                    ]);
                } else {
                    $defaults['previewing'] = false;
                }

                return $defaults;
            })
            ->toArray();
    }
}
