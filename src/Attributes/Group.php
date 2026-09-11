<?php

namespace Codewiser\Postie\Attributes;

/**
 * Subscription group
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class Group
{
    public string $name;

    public function __construct(string|\BackedEnum $name)
    {
        $this->name = $name instanceof \BackedEnum ? (string) $name->value : $name;
    }
}