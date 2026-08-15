<?php

namespace Codewiser\Postie\Traits;

trait HasTitle
{
    protected string $title;

    /**
     * Get title.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Set human-readable title.
     */
    public function title(string $title): static
    {
        $this->title = $title;

        return $this;
    }
}
