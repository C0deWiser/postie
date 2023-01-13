<?php

namespace Codewiser\Postie;

use Codewiser\Postie\Traits\HasAudience;
use Codewiser\Postie\Traits\HasChannels;
use Codewiser\Postie\Traits\HasTitle;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;

class Group implements Arrayable
{
    use HasChannels, HasAudience, HasTitle;

    protected string $icon;
    protected array $subscriptions;

    public static function make(string $title, string $icon = 'asterisk'): Group
    {
        return new static($title, $icon);
    }

    public function __construct(string $title, string $icon = 'asterisk')
    {
        $this->title = $title;
        $this->icon = $icon;
    }

    /**
     * Get group bootstrap icon class name.
     */
    public function getIcon(): string
    {
        return $this->icon;
    }

    /**
     * Set channel bootstrap icon class name (without prefix "bi bi-*").
     *
     * @see https://icons.getbootstrap.com/
     */
    public function icon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Get subscriptions on group.
     *
     * @return array<Subscription>
     */
    public function getSubscriptions(): array
    {
        return $this->subscriptions;
    }

    public function getShortcode(): string
    {
        return Str::substr(md5($this->getTitle()), 0, 2);
    }

    /**
     * Add subscription to the group.
     */
    public function add(Subscription $subscription): self
    {
        if ($subscription->getChannels()->isEmpty()) {
            $channels = [];
            foreach($this->getChannels() as $channel) {
                $channels[] = $channel;
            }
            $subscription->via($channels);
        }

        if (!$subscription->hasAudience() && $this->hasAudience()) {
            $subscription->for($this->audience);
        }

        $this->subscriptions[] = $subscription;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'shortcode' => $this->getShortcode(),
            'name' => $this->getTitle(),
            'icon' => $this->getIcon(),
        ];
    }

    public function dump(): self
    {
        dump($this);

        return $this;
    }
}
