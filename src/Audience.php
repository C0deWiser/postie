<?php

namespace Codewiser\Postie;

use Codewiser\Postie\Traits\HasTitle;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Traits\Conditionable;

class Audience implements Arrayable
{
    use HasTitle, Conditionable;

    /**
     * @var null|callable(mixed): Builder<Notifiable>
     */
    protected $builder = null;

    protected string $name;

    /**
     * Make audience definition with audience name.
     *
     * @param  string|\BackedEnum  $name  Audience name.
     * @param  string  $title  Audience human-readable title.
     */
    public static function make(string|\BackedEnum $name, string $title = ''): static
    {
        return new static($name, $title);
    }

    /**
     * @param  string|\BackedEnum  $name  Audience name.
     * @param  string  $title  Audience human-readable title.
     */
    public function __construct(string|\BackedEnum $name, string $title = '')
    {
        $this->name = $name instanceof \BackedEnum ? (string) $name->value : $name;
        $this->title = $title ?: $this->name;
    }

    /**
     * Set audience human-readable title.
     */
    public function title(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Define audience possible notifiables.
     *
     * @param  callable(mixed): Builder<Notifiable>  $builder
     */
    public function with(callable $builder): static
    {
        $this->builder = $builder;

        return $this;
    }

    /**
     * Get Builder that holds audience notifiables.
     */
    public function getBuilder(): ?Builder
    {
        return is_callable($this->builder) ? call_user_func($this->builder) : null;
    }

    /**
     * Get raw audience callable.
     *
     * @return null|callable(mixed): Builder<Notifiable>
     */
    public function getCallback(): ?callable
    {
        return $this->builder;
    }

    /**
     * Does the audience define possible notifiables?
     */
    public function hasBuilder(): bool
    {
        return is_callable($this->builder);
    }

    /**
     * Get audience name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function toArray(): array
    {
        return [
            'name'  => $this->getName(),
            'title' => $this->getTitle(),
        ];
    }
}