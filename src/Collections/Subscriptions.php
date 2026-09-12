<?php

namespace Codewiser\Postie\Collections;

use Codewiser\Postie\Audience;
use Codewiser\Postie\Group;
use Codewiser\Postie\Models\Preference;
use Codewiser\Postie\PostieService;
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
     *
     * A subscription defining its own audience matches only that audience,
     * ignoring any group audiences.
     * Otherwise it matches when the notifiable belongs to every audience
     * of the groups it is attached to (if any).
     * A subscription without any audience applies to everyone.
     */
    public function for(Model $notifiable): static
    {
        return $this->filter(
            fn(Subscription $subscription) => $subscription->isOwnAudience()
                // Own audience wins and group audiences are ignored
                ? $this->withinAudience($subscription->getAudience(), $notifiable)
                // Otherwise notifiable must belong to every group audience (if any)
                : $subscription->getGroups()->every(
                    fn(Group $group) => $this->withinAudience($group->getAudience(), $notifiable)
                )
        );
    }

    /**
     * Check whether the notifiable belongs to the given audience.
     *
     * An audience without a builder allows everyone.
     */
    protected function withinAudience(?Audience $audience, Model $notifiable): bool
    {
        return ! ($audience?->hasBuilder() ?? false)
            || (bool) $audience->getBuilder()?->find($notifiable->getKey());
    }

    /**
     * Get all defined groups.
     *
     * Groups sharing the same unique name are merged, keeping the one with richer attributes.
     */
    public function groups(): Groups
    {
        $groups = [];

        foreach ($this as $subscription) {
            foreach ($subscription->getGroups() as $group) {
                $shortcode = $group->getShortcode();

                if (! isset($groups[$shortcode]) || $group->getRank() > $groups[$shortcode]->getRank()) {
                    $groups[$shortcode] = $group;
                }
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
            // Sort subscriptions respecting group weight.
            // Ungrouped subscriptions (fallback group) go to the bottom,
            // auto-discovered ones ahead of explicitly defined.
            ->sort(function (Subscription $a, Subscription $b) {
                // Order a subscription by the biggest of its groups' effective
                // positions: group weight first, or order of appearance
                // for groups without an explicit weight.
                $key = fn(Subscription $subscription) => $subscription
                    ->getGroups()
                    ->max(fn(Group $group) => [
                        $group->getWeight(),
                        $group->getPosition() ?? PHP_INT_MAX,
                    ]);

                $keyA = $key($a);
                $keyB = $key($b);

                if ($keyA !== $keyB) {
                    return $keyA <=> $keyB;
                }

                // Among ungrouped subscriptions, put auto-discovered first.
                if ($a->getGroups()->hasFallback()
                    && $b->getGroups()->hasFallback()
                    && $a->isDiscovered() !== $b->isDiscovered()) {
                    return $a->isDiscovered() ? -1 : 1;
                }

                return 0;
            })
            // Drop resorted keys
            ->values()
            ->map(fn(Subscription $subscription) => [
                ...$subscription->toArray(),

                // Add channels respecting user preferences and routes
                'channels' => $subscription->getChannels()
                    // Order channels by default channel definitions.
                    ->orderedBy(
                        app(PostieService::class)->getChannels()->all()
                    )
                    ->withNotifiable($notifiable, $subscription,
                        $preferences->ofNotification($subscription->getNotification())
                    ),
            ])
            ->toArray();
    }
}
