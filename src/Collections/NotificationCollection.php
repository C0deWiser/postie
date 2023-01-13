<?php

namespace Codewiser\Postie\Collections;

use Codewiser\Postie\Models\Subscription;
use Codewiser\Postie\Subscription as SubscriptionDefinition;
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
    public function find(string $notification): SubscriptionDefinition
    {
        return $this
            ->sole(function (SubscriptionDefinition $definition) use ($notification) {
                return $definition->getClassName() === $notification;
            });
    }

    /**
     * Find notification definition by index of definition.
     */
    public function findByIndex(int $searchIndex): ?SubscriptionDefinition
    {
        return $this
            ->sole(function (SubscriptionDefinition $definition, $index) use ($searchIndex) {
                return $index === $searchIndex;
            });
    }

    /**
     * Get class names of notifications.
     */
    public function classNames(): array
    {
        return $this
            ->map(function (SubscriptionDefinition $notificationDefinition) {
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
            ->filter(function (SubscriptionDefinition $notificationDefinition) use ($notifiable) {
                if ($builder = $notificationDefinition->getAudience()) {
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
            // Put undefined group to the bottom
            ->sort(function (SubscriptionDefinition $a, SubscriptionDefinition $b) {
                $a = $a->getGroup() ? $a->getGroup()->getTitle() : null;
                $b = $b->getGroup() ? $b->getGroup()->getTitle() : null;

                if (is_null($a) && is_null($b)) {
                    return 0;
                } elseif (is_null($a)) {
                    return 1;
                } elseif (is_null($b)) {
                    return -1;
                } else {
                    return 0;
                }
            })
            // Drop resorted keys
            ->values()
            // Set fallback name to the undefined group
            ->map(function (SubscriptionDefinition $item) {
                if (!$item->getGroup()) {
                    $item->group(__('postie::subscriptions.fallbackGroup'));
                }

                return $item;
            })
            // Add user channels
            ->map(function (SubscriptionDefinition $definition) use ($notifiable, $subscriptions) {
                $row = $definition->toArray();

                $subscription = $subscriptions->firstByNotification($definition->getClassName());

                $row['channels'] = $definition->getChannels()
                    ->getResolvedByNotifiableSubscription($notifiable, $definition, $subscription);

                return $row;
            })
            ->toArray();
    }
}
