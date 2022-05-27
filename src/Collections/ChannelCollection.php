<?php

namespace Codewiser\Postie\Collections;

use Codewiser\Postie\ChannelDefinition;
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
        $channels = [];

        /** @var ChannelDefinition $channelDefinition */
        foreach ($this as $channelDefinition) {
            $currentChannel = $channelDefinition->toArray();

            // Если в данной записи определен текущий канал оповещения
            $userChannelStatus = $subscription && isset($subscription->channels[$channelDefinition->getName()])
                ? $subscription->channels[$channelDefinition->getName()]
                : null;
            
            $currentChannel['status'] = $channelDefinition->getStatus($notifiable, $userChannelStatus);
            $currentChannel['available'] = (bool)$notifiable->routeNotificationFor($channelDefinition->getName());
            $channels[] = $currentChannel;
        }

        return $channels;
    }
}
