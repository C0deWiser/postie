<?php

namespace Codewiser\Postie;

/**
 * @deprecated
 */
class ChannelDefinition extends Channel
{
    /**
     * Make definition with channel name.
     */
    public static function make(string $name): ChannelDefinition
    {
        return new static($name);
    }
}
