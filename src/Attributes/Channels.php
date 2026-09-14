<?php

namespace Codewiser\Postie\Attributes;

use Codewiser\Postie\Channel as ChannelDefinition;

/**
 * Subscription available channels.
 *
 * Applies the given flags to every listed channel.
 *
 * Must be declared once. To configure channels individually,
 * repeat the `Channel` attribute.
 *
 * Not provided flags are inherited from a default channel (see PostieService::$channels).
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Channels
{
    /**
     * @var array<int, string>
     */
    public array $names;

    /**
     * @param  string[]  $names Channel names.
     */
    public function __construct(
        array $names,
        public ?bool $default = null,
        public ?bool $forced = null,
        public ?bool $hidden = null
    ) {
        $this->names = $names;
    }

    /**
     * Build a channel definition for every given channel name.
     *
     * @return array<int, ChannelDefinition>
     */
    public function toChannels(): array
    {
        return array_map(
            fn(string $name) => (new Channel($name, $this->default, $this->forced, $this->hidden))->toChannel(),
            $this->names
        );
    }
}