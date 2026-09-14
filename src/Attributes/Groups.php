<?php

namespace Codewiser\Postie\Attributes;

/**
 * Subscription groups.
 *
 * Must be declared once. To attach a subscription to several groups,
 * list all of them in a single attribute or repeat the `Group` attribute.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
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