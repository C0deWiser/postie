<?php

namespace Codewiser\Postie\Collections;

use Codewiser\Postie\ChannelDefinition;
use Codewiser\Postie\Models\Subscription;
use Codewiser\Postie\NotificationDefinition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class NotificationCollection extends Collection
{
    /**
     * Find notification definition by class name.
     */
    public function find(string $notification):?NotificationDefinition
    {
        return $this
            ->sole(function (NotificationDefinition $definition) use ($notification) {
            return $definition->getClassName() === $notification;
        });
    }

    /**
     * Find notification definition by index of definition.
     */
    public function findByIndex(int $searchIndex):?NotificationDefinition
    {
        return $this
            ->sole(function (NotificationDefinition $definition, $index) use ($searchIndex) {
                return $index === $searchIndex;
            });
    }

    /**
     * Get class names of notifications.
     */
    public function classNames():array
    {
        return $this
            ->map(function (NotificationDefinition $notificationDefinition) {
                return $notificationDefinition->getClassName();
            })
            ->toArray();
    }

    /**
     * Filter notifiable relevant notifications.
     */
    public function for(Model $notifiable): self
    {
        return $this
            ->filter(function (NotificationDefinition $notificationDefinition) use ($notifiable) {
                return (bool)$notificationDefinition->getAudienceBuilder()->find($notifiable->getKey());
            });
    }

    /**
     * Build User Notifications with channel statuses
     */
    public function buildUserNotificationsWithChannelStatuses(SubscriptionCollection $subscriptions): array
    {
        $result = [];
        /** @var NotificationDefinition $notificationDefinition */
        foreach ($this as $notificationDefinition) {
            $row = [];
            $row['notification'] = $notificationDefinition->getClassName();
            $row['title'] = $notificationDefinition->getTitle();

            $subscription = $subscriptions->firstByNotification($notificationDefinition->getClassName());

            $row['channels'] = $notificationDefinition->getChannels()->getResolvedByNotifiableSubscription($subscription);

            $result[] = $row;
        }

        return $result;
    }
}
