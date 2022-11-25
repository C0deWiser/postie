<?php

namespace Codewiser\Postie\Collections;

use Codewiser\Postie\ChannelDefinition;
use Codewiser\Postie\Models\Subscription;
use Codewiser\Postie\NotificationDefinition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\ItemNotFoundException;
use Illuminate\Support\MultipleItemsFoundException;

class NotificationCollection extends Collection
{
    /**
     * Find notification definition by class name.
     *
     * @throws ItemNotFoundException
     * @throws MultipleItemsFoundException
     */
    public function find(string $notification): NotificationDefinition
    {
        return $this
            ->sole(function (NotificationDefinition $definition) use ($notification) {
                return $definition->getClassName() === $notification;
            });
    }

    /**
     * Find notification definition by index of definition.
     */
    public function findByIndex(int $searchIndex): ?NotificationDefinition
    {
        return $this
            ->sole(function (NotificationDefinition $definition, $index) use ($searchIndex) {
                return $index === $searchIndex;
            });
    }

    /**
     * Get class names of notifications.
     */
    public function classNames(): array
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
                if ($builder = $notificationDefinition->getAudienceBuilder()) {
                    return (bool)$builder->find($notifiable->getKey());
                } else {
                    return false;
                }
            });
    }

    /**
     * Build User Notifications with channel statuses.
     */
    public function buildUserNotificationsWithChannelStatuses($notifiable): array
    {
        /** @var SubscriptionCollection $subscriptions */
        $subscriptions = Subscription::for($notifiable, $this->classNames())->get();

        return $this
            ->map(function (NotificationDefinition $definition) use ($notifiable, $subscriptions) {
                $row = $definition->toArray();

                $subscription = $subscriptions->firstByNotification($definition->getClassName());

                $row['channels'] = $definition->getChannels()->getResolvedByNotifiableSubscription($notifiable, $subscription);

                return $row;
            })
            ->toArray();
    }
}
