<?php

namespace Codewiser\Postie\Traits;

trait HasTitle
{
    protected string $title;

    /**
     * Get notification title.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Set notification human-readable title.
     */
    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }
}
