<?php

namespace Codewiser\Postie\Collections;

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
            ->first(function (NotificationDefinition $definition) use ($notification) {
            return $definition->getClassName() === $notification;
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
}
