<?php

namespace Codewiser\Postie\Collections;

use Codewiser\Postie\Channel;
use Codewiser\Postie\Models\Preference;
use Codewiser\Postie\Subscription;
use Illuminate\Database\Eloquent\Model;
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
            'previewing' => $subscription->hasPreview()
                ? route('postie.preview', [
                    'channel'      => $channel->getName(),
                    'notification' => $subscription->getNotification()
                ])
                : null,
        ])->toArray();
    }
}
