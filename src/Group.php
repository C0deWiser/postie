<?php

namespace Codewiser\Postie;

use Codewiser\Postie\Traits\HasAudience;
use Codewiser\Postie\Traits\HasChannels;
use Codewiser\Postie\Traits\HasTitle;
use Codewiser\Postie\Traits\HasVarieties;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;

class Group implements Arrayable
{
    use HasChannels, HasAudience, HasTitle, HasVarieties;

    protected array $subscriptions;
    protected bool $fallback = false;
    protected int $weight = 0;

    /**
     * Fallback group will be appended to any subscription without groups.
     */
    public static function fallback(): static
    {
        $group = new static(
            __('postie::subscriptions.fallbackGroup')
        );

        $group->fallback = true;
        $group->weight = PHP_INT_MAX;

        return $group;
    }

    /**
     * Make new group with given title.
     *
     * @param  string  $title Group title.
     * @param  string  $icon Group icon bootstrap class name (without prefix "bi bi-*").
     */
    public static function make(string $title, string $icon = 'asterisk'): static
    {
        return new static($title, $icon);
    }

    /**
     * @param  string  $title Group title.
     * @param  string  $icon Group icon bootstrap class name (without prefix "bi bi-*").
     */
    public function __construct(string $title, protected string $icon = 'asterisk')
    {
        $this->title = $title;
    }

    /**
     * Add subscription to the group.
     */
    public function add(Subscription $subscription): static
    {
        if ($subscription->getChannels()->isEmpty()) {
            // we cant just pass collection->toArray, as every channel is arrayable too
            $channels = [];
            foreach ($this->getChannels() as $channel) {
                $channels[] = $channel;
            }
            $subscription->via($channels);
        }

        if (! $subscription->getVarieties()) {
            $subscription->varieties($this->varieties);
        }

        if (! $subscription->hasAudience() && $this->hasAudience()) {
            $subscription->for($this->audience);
        }

        $this->subscriptions[] = $subscription;

        return $this;
    }

    /**
     * Set channel icon bootstrap class name (without prefix "bi bi-*").
     *
     * @see https://icons.getbootstrap.com/
     */
    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Set group weight. Havier groups will fall down to the bottom of list.
     */
    public function weight(int $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * Is fallback group?
     */
    public function isFallback(): bool
    {
        return $this->fallback;
    }

    /**
     * Get group unique shortcode (used for routing).
     */
    public function getShortcode(): string
    {
        return Str::substr(md5($this->getTitle()), 0, 4);
    }

    /**
     * Get group icon bootstrap class name.
     */
    public function getIcon(): string
    {
        return $this->icon;
    }

    /**
     * Get group weight.
     */
    public function getWeight(): int
    {
        return $this->weight;
    }

    /**
     * Get subscriptions appended to the group.
     *
     * @return array<int, Subscription>
     */
    public function getSubscriptions(): array
    {
        return $this->subscriptions;
    }

    public function toArray(): array
    {
        return [
            'shortcode' => $this->getShortcode(),
            'name'      => $this->getTitle(),
            'icon'      => $this->getIcon(),
            'fallback'  => $this->isFallback(),
            'weight'    => $this->getWeight(),
        ];
    }
}
