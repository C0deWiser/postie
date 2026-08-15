<?php

namespace Codewiser\Postie;

use Codewiser\Postie\Traits\HasTitle;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;

class Channel implements Arrayable
{
    use HasTitle;

    protected string $icon;
    protected ?string $subtitle = null;

    /**
     * Make channel definition with channel name.
     *
     * @param  string  $name  Channel name (mail, sms, etc).
     * @param  bool  $default  Default state (subscribed or not).
     * @param  bool  $forced  Can user change subscription?
     * @param  bool  $hidden  Hide channel from dashboard?
     */
    public static function via(string $name, bool $default = true, bool $forced = false, bool $hidden = false): static
    {
        return new static($name, $default, $forced, $hidden);
    }

    /**
     * @param  string  $name  Channel name (mail, sms, etc).
     * @param  bool  $default  Default state (subscribed or not).
     * @param  bool  $forced  Can user change subscription?
     * @param  bool  $hidden  Hide channel from dashboard?
     */
    public function __construct(
        protected string $name,
        protected bool $default = true,
        protected bool $forced = false,
        protected bool $hidden = false
    ) {
        $this->title = (string) Str::of(class_basename($this->name))->snake()->studly();

        $this->icon = match ($this->name) {
            'skype', 'slack', 'steam', 'rocket',
            'spotify', 'facebook', 'linkedin',
            'mastodon', 'telegram', 'whatsapp' => 'bi bi-'.$this->name,
            'mail'                             => 'bi bi-envelope',
            'sms'                              => 'bi bi-chat',
            'database'                         => 'bi bi-bell',
            'broadcast'                        => 'bi bi-app-indicator',
            default                            => 'bi bi-record-circle',
        };
    }

    /**
     * Set channel human-readable title.
     */
    public function title(string $title): static
    {
        $clone = clone $this;
        $clone->title = $title;
        return $clone;
    }

    /**
     * Set channel description.
     */
    public function subtitle(string $subtitle): static
    {
        $clone = clone $this;
        $clone->subtitle = $subtitle;
        return $clone;
    }

    /**
     * Set channel default.
     */
    public function default(bool $default): static
    {
        $clone = clone $this;
        $clone->default = $default;
        return $clone;
    }

    /**
     * Set channel active by default.
     */
    public function active(): static
    {
        return $this->default(true);
    }

    /**
     * Set channel passive by default.
     */
    public function passive(): static
    {
        return $this->default(false);
    }

    /**
     * Set if channel is forced to use default state.
     */
    public function forced(bool $forced = true): static
    {
        $clone = clone $this;
        $clone->forced = $forced;
        return $clone;
    }

    /**
     * Set if channel should be hidden from dashboard.
     */
    public function hidden(bool $hidden = true): static
    {
        $clone = clone $this;
        $clone->hidden = $hidden;
        return $clone;
    }

    /**
     * Set channel icon bootstrap class name (without prefix "bi bi-*").
     *
     * @see https://icons.getbootstrap.com/
     */
    public function icon(string $icon): static
    {
        $clone = clone $this;
        $clone->icon = 'bi bi-'.$icon;
        return $clone;
    }

    /**
     * Get channel name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get channel description.
     */
    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    /**
     * If channel enabled by default.
     */
    public function getDefault(): bool
    {
        return $this->default;
    }

    /**
     * Is channel forced to use default state.
     */
    public function getForced(): bool
    {
        return $this->forced;
    }

    /**
     * Should channel be hidden from dashboard?
     */
    public function getHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * Get channel icon bootstrap class name.
     */
    public function getIcon(): string
    {
        return $this->icon;
    }

    /**
     * Get channel activity respecting user preferences.
     *
     * @param  null|bool  $prefs  User preferences about this channel.
     */
    public function getPreferences(bool $prefs = null): bool
    {
        if ($this->forced || is_null($prefs)) {
            return $this->default;
        }

        return $prefs;
    }

    public function toArray(): array
    {
        return [
            'name'     => $this->getName(),
            'title'    => $this->getTitle(),
            'subtitle' => $this->getSubtitle(),
            'default'  => $this->getDefault(),
            'forced'   => $this->getForced(),
            'hidden'   => $this->getHidden(),
            'icon'     => $this->getIcon(),
        ];
    }
}
