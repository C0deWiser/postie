<?php

namespace Codewiser\Postie;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;

class Channel implements Arrayable
{
    protected string $name;
    protected string $title;
    protected bool $default = true;
    protected bool $forced = false;
    protected bool $hidden = false;
    protected string $icon;
    protected ?string $subtitle = null;

    /**
     * Make definition with channel name.
     */
    public static function via(string $name, bool $active = true, bool $forced = false, bool $hidden = false): Channel
    {
        return new static($name, $active, $forced, $hidden);
    }

    /**
     * @param string $name Channel name.
     */
    public function __construct(string $name, bool $active = true, bool $forced = false, bool $hidden = false)
    {
        $this->name = $name;
        $this->title = (string)Str::of(class_basename($name))->snake()->studly();
        $this->default = $active;
        $this->forced = $forced;
        $this->hidden = $hidden;

        switch ($name) {
            case 'skype':
            case 'slack':
            case 'steam':
            case 'rocket':
            case 'spotify':
            case 'facebook':
            case 'linkedin':
            case 'mastodon':
            case 'telegram':
            case 'whatsapp':
                $this->icon($name);
                break;
            case 'mail':
                $this->icon('envelope');
                break;
            case 'sms':
                $this->icon('chat');
                break;
            case 'database':
                $this->icon('bell');
                break;
            case 'broadcast':
                $this->icon('app-indicator');
                break;
            default:
                $this->icon('record-circle');
                break;
        }
    }

    /**
     * Get channel name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get channel human readable title.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Get channel description.
     */
    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    /**
     * Check if channel enabled by default.
     */
    public function getDefault(): bool
    {
        return $this->default;
    }

    /**
     * Check if channel forced to use default value.
     */
    public function getForced(): bool
    {
        return $this->forced;
    }

    /**
     * Check if channel should be hidden from user interface.
     */
    public function getHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * Get channel bootstrap icon class name.
     */
    public function getIcon(): string
    {
        return $this->icon;
    }

    /**
     * Set channel human readable title.
     */
    public function title(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Set channel description.
     */
    public function subtitle(string $subtitle): self
    {
        $this->subtitle = $subtitle;
        return $this;
    }

    /**
     * Set channel default.
     */
    public function default(bool $default): self
    {
        $this->default = $default;
        return $this;
    }

    /**
     * Set channel active by default.
     */
    public function active(): self
    {
        return $this->default(true);
    }

    /**
     * Set channel passive by default.
     */
    public function passive(): self
    {
        return $this->default(false);
    }

    /**
     * Set if channel is forced to use default state.
     */
    public function forced(bool $forced = true): self
    {
        $this->forced = $forced;
        return $this;
    }

    /**
     * Set if channel should be hidden from user interface.
     */
    public function hidden(bool $hidden = true): self
    {
        $this->hidden = $hidden;
        return $this;
    }

    /**
     * Set channel bootstrap icon class name (without prefix "bi bi-*").
     *
     * @see https://icons.getbootstrap.com/
     */
    public function icon(string $icon): self
    {
        $this->icon = 'bi bi-'.$icon;
        return $this;
    }

    /**
     * Check if channel enabled using user preferences.
     */
    public function getStatus($notifiable, bool $userChannelStatus = null): bool
    {
        if ($this->forced || is_null($userChannelStatus)) {
            return $this->default;
        }

        return $userChannelStatus;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->getName(),
            'title' => $this->getTitle(),
            'subtitle' => $this->getSubtitle(),
            'default' => $this->getDefault(),
            'forced' => $this->getForced(),
            'hidden' => $this->getHidden(),
            'icon' => $this->getIcon(),
        ];
    }
}
