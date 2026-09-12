<?php

namespace Codewiser\Postie\Attributes;

use Codewiser\Postie\Channel as ChannelDefinition;
use Codewiser\Postie\PostieService;
use function app;

/**
 * Subscription available channel.
 *
 * May be applied several times.
 *
 * Not provided flags are inherited from a default channel (see PostieService::$channels).
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class Channel
{
    public function __construct(
        public string $name,
        public ?bool $default = null,
        public ?bool $forced = null,
        public ?bool $hidden = null
    ) {
        //
    }

    public function toChannel(): ChannelDefinition
    {
        $channel = app(PostieService::class)
            ->getChannels()
            ->first(fn(ChannelDefinition $c) => $c->getName() === $this->name);

        $channel = $channel ? clone $channel : new ChannelDefinition($this->name);

        if (! is_null($this->default)) {
            $channel = $channel->default($this->default);
        }

        if (! is_null($this->forced)) {
            $channel = $channel->forced($this->forced);
        }

        if (! is_null($this->hidden)) {
            $channel = $channel->hidden($this->hidden);
        }

        return $channel;
    }
}