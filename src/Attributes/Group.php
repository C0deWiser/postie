<?php

namespace Codewiser\Postie\Attributes;

/**
 * Subscription group
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class Group
{
    public function __construct(public string $name)
    {
        //
    }
}