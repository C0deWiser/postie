<?php

namespace Codewiser\Postie\Collections;

use Codewiser\Postie\Models\Preference;
use Codewiser\Postie\Subscription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\ItemNotFoundException;
use Illuminate\Support\MultipleItemsFoundException;

/**
 * Collection of Subscriptions.
 *
 * @extends Collection<int, Subscription>
 */
class Subscriptions extends Collection
{
    /**
     * Find subscription definition by notification.
     *
     * @param  class-string<Notification>  $notification
     *
     * @throws ItemNotFoundException
     * @throws MultipleItemsFoundException
     */
    public function find(string $notification): Subscription
    {
        return $this
            ->sole(fn(Subscription $subscription) => $subscription->getNotification() === $notification);
    }

    /**
     * Get listing of subscriptions names.
     *
     * @return array<int, class-string<Notification>>
     */
    public function names(): array
    {
        return $this
            ->map(fn(Subscription $subscription) => $subscription->getNotification())
            ->toArray();
    }

    /**
     * Filter notifiable relevant subscriptions.
     */
    public function for(Model $notifiable): static
    {
        return $this->filter(
            fn(Subscription $subscription) => $subscription
                ->getAudience()?->find($notifiable->getKey())
        );
    }

    /**
     * Get all defined groups.
     */
    public function groups(): Groups
    {
        $groups = [];

        foreach ($this as $subscription) {
            foreach ($subscription->getGroups() as $group) {
                $groups[$group->getShortcode()] = $group;
            }
        }

        return new Groups(array_values($groups));
    }

    /**
     * Filter subscriptions by given group shortcode.
     */
    public function filterByGroup(string $shortcode): static
    {
        return $this->filter(
            fn(Subscription $subscription) => $subscription->getGroups()->filterByShortcode($shortcode)->isNotEmpty()
        );
    }

    /**
     * Append user preferences.
     *
     * @return array<int, array>
     */
    public function withNotifiable(Model $notifiable): array
    {
        /** @var Preferences $preferences */
        $preferences = Preference::for($notifiable, $this->names())->get();

        return $this
            // Put subscriptions with fallback group to the bottom
            ->sort(fn(
                Subscription $a,
                Subscription $b
            ) => ($a->getGroups()->hasFallback() ? 1 : 0) - ($b->getGroups()->hasFallback() ? 1 : 0))
            // Drop resorted keys
            ->values()
            ->map(fn(Subscription $subscription) => [
                ...$subscription->toArray(),

                // Add channels respecting user preferences and routes
                'channels' => $subscription->getChannels()
                    ->withNotifiable($notifiable, $subscription,
                        $preferences->ofNotification($subscription->getNotification())
                    ),
            ])
            ->toArray();
    }
}
