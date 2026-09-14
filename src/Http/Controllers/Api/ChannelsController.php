<?php

namespace Codewiser\Postie\Http\Controllers\Api;

use Codewiser\Postie\Channel;
use Codewiser\Postie\Http\Controllers\Controller;
use Codewiser\Postie\PostieService;
use Illuminate\Http\Request;

class ChannelsController extends Controller
{
    /**
     * Get all defined channels.
     */
    public function index(Request $request, PostieService $postie)
    {
        return response()->json([
            'data' => $postie->getChannels()->values()->toArray(),
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
            'data' => $channel->toArray(),
        ]);
    }
}