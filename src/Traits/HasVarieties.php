<?php

namespace Codewiser\Postie\Traits;

use Codewiser\Postie\Subscription;

trait HasVarieties
{
    /**
     * @var array<array-key, string>
     */
    protected array $varieties = [];

    /**
     * Get notification varieties.
     */
    public function getVarieties(): array
    {
        return $this->varieties;
    }

    /**
     * Set varieties for notification (EXPERIMENTAL).
     */
    public function varieties(array $varieties): static
    {
        $this->varieties = $varieties;

        return $this;
    }
}