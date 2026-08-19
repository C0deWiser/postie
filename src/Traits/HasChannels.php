<?php

namespace Codewiser\Postie\Traits;

use Codewiser\Postie\Channel;
use Codewiser\Postie\Collections\Channels;

trait HasChannels
{
    protected array $channels = [];

    /**
     * Set notification available channels.
     *
     * @param  array<int, string|Channel>|string|Channel  $channels
     */
    public function via(array|string|Channel $channels): static
    {
        if (! is_array($channels)) {
            $channels = func_get_args();
        }

        $this->channels = array_map(
            fn($channel) => is_string($channel)
                ? new Channel($channel)
                : $channel,
            $channels
        );

        return $this;
    }

    /**
     * Get notification available channels.
     */
    public function getChannels(): Channels
    {
        return new Channels($this->channels);
    }
}
