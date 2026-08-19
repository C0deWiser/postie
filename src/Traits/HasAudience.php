<?php

namespace Codewiser\Postie\Traits;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Notifications\Notifiable;

trait HasAudience
{
    /**
     * @var null|callable
     */
    protected $audience = null;

    /**
     * Define notification possible audience.
     *
     * @param callable(mixed): Builder<Notifiable> $audience
     */
    public function for(callable $audience): static
    {
        $this->audience = $audience;

        return $this;
    }

    /**
     * @deprecated use for()
     */
    public function audience(callable $audienceBuilder): static
    {
        return $this->for($audienceBuilder);
    }

    /**
     * Get Builder that holds notification audience.
     */
    public function getAudience(): ?Builder
    {
        return is_callable($this->audience) ? call_user_func($this->audience) : null;
    }

    public function hasAudience(): bool
    {
        return is_callable($this->audience);
    }
}
