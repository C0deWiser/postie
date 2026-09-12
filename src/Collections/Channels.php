<?php

namespace Codewiser\Postie\Collections;

use Codewiser\Postie\Channel;
use Codewiser\Postie\Models\Preference;
use Codewiser\Postie\Subscription;
use Illuminate\Support\Collection;
use Illuminate\Support\ItemNotFoundException;
use Illuminate\Support\MultipleItemsFoundException;

/**
 * Collection of Channels.
 *
 * @extends Collection<int, Channel>
 */
class Channels extends Collection
{
    /**
     * Find channel definition by its name.
     *
     * @throws ItemNotFoundException
     * @throws MultipleItemsFoundException
     */
    public function find(string $channel): Channel
    {
        return $this->sole(
            fn(Channel $definition) => $definition->getName() === $channel
        );
    }

    /**
     * Get listing of channels names.
     *
     * @return array<int, string>
     */
    public function names(): array
    {
        return $this
            ->map(fn(Channel $channel) => $channel->getName())
            ->toArray();
    }

    /**
     * Order channels by predefined channel definitions.
     *
     * Channels are placed according to the order of the given definitions list.
     * Channels not found in the definitions keep their relative order and go last.
     *
     * @param  array<int, Channel>  $predefined
     */
    public function orderedBy(array $predefined): static
    {
        $names = array_map(
            fn(Channel $channel) => $channel->getName(),
            array_values($predefined)
        );

        $position = fn(Channel $channel) => ($offset = array_search($channel->getName(), $names, true)) === false
            ? PHP_INT_MAX
            : $offset;

        return $this->sort(
            fn(Channel $a, Channel $b) => $position($a) <=> $position($b)
        )->values();
    }

    /**
     * Get channels respecting notifiable preferences and routes availability.
     *
     * @return array<int, array>
     */
    public function withNotifiable(
        object $notifiable,
        Subscription $subscription,
        Preference $preference = null
    ): array {
        return $this->map(fn(Channel $channel) => [

            ...$channel->toArray(),

            // Merge channel defaults with user prefs.
            'status'     => $channel->getPreferences(
                $preference?->channels[$channel->getName()] ?? null
            ),

            // Broadcast channel is always available.
            // Any other channel require notifiable to has a route.
            'available'  =>
                $channel->getName() == 'broadcast' ||
                $notifiable->routeNotificationFor($channel->getName()),

            // Notification preview route
            'previewing' => $subscription->hasPreview($channel, $notifiable)
                ? route('postie.preview', [
                    'channel'      => $channel->getName(),
                    'notification' => $subscription->getNotification()
                ])
                : null,
        ])->toArray();
    }

    /**
     * Get channels and its states respecting user preferences.
     *
     * @param  array<string, bool>  $prefs  User prefernces.
     *
     * @return array<string, bool> Actual prefernces.
     */
    public function getPreferences(array $prefs): array
    {
        return $this
            ->mapWithKeys(fn(Channel $channel) => [
                $channel->getName() => $channel->getPreferences($prefs[$channel->getName()] ?? null)
            ])
            ->toArray();
    }
}
