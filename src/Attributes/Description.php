<?php

namespace Codewiser\Postie\Attributes;

/**
 * Subscription description
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Description
{
    public function __construct(public string $description)
    {
        //
    }
}