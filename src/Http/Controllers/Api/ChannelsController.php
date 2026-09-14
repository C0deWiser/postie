<?php

namespace Codewiser\Postie\Http\Controllers\Api;

use Codewiser\Postie\Channel;
use Codewiser\Postie\Collections\Channels;
use Codewiser\Postie\Http\Controllers\Controller;
use Codewiser\Postie\PostieService;
use Illuminate\Http\Request;

class ChannelsController extends Controller
{
    /**
     * Get all defined channels with availability for the current user.
     */
    public function index(Request $request, PostieService $postie)
    {
        return response()->json([
            'data' => $this->withAvailability($request->user(), $postie->getChannels()),
        ]);
    }

    /**
     * Get a defined channel by its name.
     */
    public function show(Request $request, PostieService $postie, string $channel)
    {
        $channel = $postie->getChannels()->first(
            fn(Channel $definition) => $definition->getName() === $channel
        );

        abort_unless($channel !== null, 404);

        return response()->json([
            'data' => $this->available($request->user(), $channel),
        ]);
    }

    /**
     * Map channels adding availability for the given notifiable.
     *
     * @return array<int, array>
     */
    protected function withAvailability(object $notifiable, Channels $channels): array
    {
        return $channels
            ->values()
            ->map(fn(Channel $channel) => $this->available($notifiable, $channel))
            ->toArray();
    }

    /**
     * Compose channel payload: definition plus availability.
     *
     * A channel is available when the notifiable has a route to it,
     * or it is a broadcast channel.
     *
     * @return array
     */
    protected function available(object $notifiable, Channel $channel): array
    {
        return [
            ...$channel->toArray(),
            'available' => $channel->getName() == 'broadcast'
                || (bool) $notifiable->routeNotificationFor($channel->getName()),
        ];
    }
}