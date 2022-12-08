<?php

namespace Codewiser\Postie;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;

class Group implements Arrayable
{
    protected string $name;
    protected string $icon;
    protected array $subscriptions;

    public static function make(string $name, string $icon = 'asterisk'): Group
    {
        return new static($name, $icon);
    }

    public function __construct(string $name, string $icon = 'asterisk')
    {
        $this->name = $name;
        $this->icon = $icon;
    }

    /**
     * Get group name.
     */
    public function getName(): string
    {
        return $this->name;
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
        return Str::substr(md5($this->getName()), 0, 2);
    }

    /**
     * Add subscription to the group.
     */
    public function add(Subscription $subscription): self
    {
        $this->subscriptions[] = $subscription;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'shortcode' => $this->getShortcode(),
            'name' => $this->getName(),
            'icon' => $this->getIcon(),
        ];
    }

    public function dump(): self
    {
        dump($this);

        return $this;
    }
}
