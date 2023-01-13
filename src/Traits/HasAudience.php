<?php

namespace Codewiser\Postie\Traits;

use Closure;
use Codewiser\Postie\Subscription;
use Illuminate\Contracts\Database\Eloquent\Builder;

trait HasAudience
{
    protected ?Closure $audience = null;

    /**
     * Define notification possible audience.
     * Closure should return Builder with notifiable objects.
     */
    public function for(Closure $audience): Subscription
    {
        $this->audience = $audience;

        return $this;
    }

    /**
     * @deprecated use for()
     */
    public function audience(Closure $audienceBuilder): Subscription
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
