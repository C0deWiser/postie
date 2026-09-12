<?php

namespace Codewiser\Postie;

use Codewiser\Postie\Traits\HasAudience;
use Codewiser\Postie\Traits\HasChannels;
use Codewiser\Postie\Traits\HasTitle;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;

class Group implements Arrayable
{
    use HasChannels, HasAudience, HasTitle, Conditionable;

    protected array $subscriptions = [];
    protected bool $fallback = false;
    protected int $weight = 0;
    protected ?int $position = null;

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
     *
     * @param  Subscription|class-string<Notification>  $subscription  A subscription or a notification class name.
     */
    public function add(Subscription|string $subscription): static
    {
        if (! $subscription instanceof Subscription) {
            $subscription = Subscription::to($subscription);
        }

        if ($subscription->getChannels()->isEmpty()) {
            $subscription->via(
                $this->getChannels()->all()
            );
        }

        if (! $subscription->isOwnAudience()
            && ! $subscription->getAudience()
            && ($this->getAudience()?->hasBuilder() ?? false)) {
            $subscription->inheritAudience($this->getAudience());
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
     *
     * Groups without an explicit weight keep their order of appearance.
     */
    public function weight(int $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * Set group position of appearance among predefined group definitions.
     */
    public function position(int $position): static
    {
        $this->position = $position;

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
     * Get group position of appearance among predefined group definitions.
     */
    public function getPosition(): ?int
    {
        return $this->position;
    }

    /**
     * Get number of explicitly defined attributes.
     *
     * Used to merge groups sharing the same shortcode, keeping the richer one.
     */
    public function getRank(): int
    {
        return (int) ($this->getIcon() !== 'asterisk')
            + (int) ($this->getWeight() !== 0)
            + (int) ($this->getAudience()?->hasBuilder() ?? false);
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
