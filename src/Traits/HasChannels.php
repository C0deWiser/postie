<?php

namespace Codewiser\Postie\Traits;

use Codewiser\Postie\Channel;
use Codewiser\Postie\Collections\ChannelCollection;
use Codewiser\Postie\Subscription;

trait HasChannels
{
    protected array $channels = [];

    /**
     * Set notification available channels.
     *
     * @param Channel|string|array $channels
     */
    public function via($channels): Subscription
    {
        if (!is_array($channels)) {
            $channels = func_get_args();
        }

        foreach ($channels as $i => $channel) {
            if (is_string($channel)) {
                $channels[$i] = new Channel($channel);
            }
        }

        $this->channels = $channels;

        return $this;
    }

    /**
     * Get notification available channels.
     */
    public function getChannels(): ChannelCollection
    {
        return ChannelCollection::make($this->channels ?? []);
    }
}
