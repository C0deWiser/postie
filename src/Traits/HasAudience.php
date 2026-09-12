<?php

namespace Codewiser\Postie\Traits;

use Codewiser\Postie\Audience;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Notifications\Notifiable;

trait HasAudience
{
    protected ?Audience $audience = null;

    /**
     * Whether the audience was set explicitly (via `for()`),
     * rather than inherited from a group.
     */
    protected bool $audienceIsOwn = false;

    /**
     * Define notification possible audience.
     *
     * Accepts a predefined Audience by its name, or a new Audience object.
     *
     * @param  Audience|string|\BackedEnum  $audience
     */
    public function for(Audience|string|\BackedEnum $audience): static
    {
        $this->audience = $audience instanceof Audience
            ? $audience
            : $this->getService()->findAudience($audience);

        $this->audienceIsOwn = true;

        return $this;
    }

    /**
     * Get audience definition.
     */
    public function getAudience(): ?Audience
    {
        return $this->audience;
    }

    /**
     * Whether the audience is owned explicitly (via `for()`),
     * rather than inherited from a group.
     */
    public function isOwnAudience(): bool
    {
        return $this->audienceIsOwn;
    }

    /**
     * Set an audience inherited from a group.
     */
    public function inheritAudience(Audience $audience): void
    {
        $this->audience = $audience;
    }
}