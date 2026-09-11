<?php

namespace Codewiser\Postie\Collections;

use Codewiser\Postie\Group;
use Illuminate\Support\Collection;

/**
 * Collection of Groups.
 *
 * @extends Collection<int, Group>
 */
class Groups extends Collection
{
    /**
     * Filter collection with group shortcode.
     */
    public function filterByShortcode(string $shortcode): static
    {
        return $this->filter(
            fn(Group $group) => $group->getShortcode() === $shortcode
        );
    }

    /**
     * Check if collection contains fallback group?
     */
    public function hasFallback(): bool
    {
        return $this
            ->filter(
                fn(Group $group) => $group->isFallback()
            )
            ->isNotEmpty();
    }

    /**
     * Sort groups by its weight.
     */
    public function reorder(): static
    {
        return $this->sort(function (Group $a, Group $b) {

            if ($a->getWeight() === $b->getWeight()) {
                return 0;
            } else {
                return $a->getWeight() < $b->getWeight() ? -1 : 1;
            }
        });
    }

    /**
     * Order groups by predefined group definitions.
     *
     * Groups are placed according to the order of the given definitions list.
     * Groups not found in the definitions keep their relative order and go last.
     *
     * @param  array<int, Group>  $predefined
     */
    public function orderedBy(array $predefined): static
    {
        $shortcodes = array_map(
            fn(Group $group) => $group->getShortcode(),
            array_values($predefined)
        );

        $index = fn(Group $group) => ($position = array_search($group->getShortcode(), $shortcodes, true)) === false
            ? PHP_INT_MAX
            : $position;

        return $this->sort(
            fn(Group $a, Group $b) => $index($a) <=> $index($b)
        );
    }
}