<?php

namespace Codewiser\Postie\Attributes;

/**
 * Subscription title
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Subject
{
    public function __construct(public string $title)
    {
        //
    }
}