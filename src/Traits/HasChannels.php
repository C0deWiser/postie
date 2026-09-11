<?php

namespace Codewiser\Postie\Traits;

use Codewiser\Postie\Channel;
use Codewiser\Postie\Collections\Channels;

trait HasChannels
{
    use HasService;

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

        $defaults = $this->getService()->getChannels();

        $this->channels = array_map(
            function (string|Channel $channel) use ($defaults) {
                if ($channel instanceof Channel) {
                    return $channel;
                }

                $definition = $defaults->first(fn(Channel $c) => $c->getName() === $channel);

                return $definition ? clone $definition : new Channel($channel);
            },
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
