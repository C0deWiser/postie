<?php

namespace Codewiser\Postie\Collections;

use Codewiser\Postie\Audience;
use Illuminate\Support\Collection;

/**
 * Collection of Audience definitions.
 *
 * @extends Collection<int, Audience>
 */
class Audiences extends Collection
{
    /**
     * Find audience definition by its name.
     */
    public function find(string|\BackedEnum $name): ?Audience
    {
        $name = $name instanceof \BackedEnum ? (string) $name->value : $name;

        return $this->first(
            fn(Audience $audience) => $audience->getName() === $name
        );
    }
}