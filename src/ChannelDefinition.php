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
    public static function make(string $name): Channel
    {
        return new static($name);
    }
}
