<?php

namespace Codewiser\Postie\Attributes;

/**
 * Subscription groups.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class Groups
{
    /**
     * @var array<int, string>
     */
    public array $names;

    /**
     * @param  string[]  $names Group names.
     */
    public function __construct(array $names)
    {
        $this->names = $names;
    }
}