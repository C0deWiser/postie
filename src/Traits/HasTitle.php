<?php

namespace Codewiser\Postie\Traits;

use Codewiser\Postie\Subscription;

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
     * Set notification human readable title.
     */
    public function title(string $title): Subscription
    {
        $this->title = $title;

        return $this;
    }
}
