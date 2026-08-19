<?php

namespace Codewiser\Postie;

use Codewiser\Postie\Collections\Groups;
use Codewiser\Postie\Traits\HasAudience;
use Codewiser\Postie\Traits\HasChannels;
use Codewiser\Postie\Traits\HasTitle;
use Codewiser\Postie\Traits\HasVarieties;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class Subscription implements Arrayable
{
    use HasChannels, HasAudience, HasTitle, HasVarieties;

    protected ?string $description = null;
    /**
     * @var null|callable
     */
    protected $preview = null;
    /**
     * @var array<int, Group>
     */
    protected array $groups = [];

    /**
     * Make subscription definition using notification class name.
     *
     * @param  class-string<Notification>  $notification
     */
    public static function to(string $notification): static
    {
        return new static($notification);
    }

    /**
     * @param  class-string<Notification>  $notification  Notification class name.
     */
    public function __construct(protected string $notification)
    {
        $this->title = (string) Str::of(class_basename($notification))->snake()->studly();
    }

    /**
     * Put subscription to a group.
     */
    public function group(Group|string $group): static
    {
        $this->groups[] = $group instanceof Group ? $group : new Group($group);

        return $this;
    }

    /**
     * Set subscription human-readable description.
     */
    public function description(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Set notification preview.
     *
     * Callback will get 'channel' and 'notifiable' parameters and should return any renderable content.
     *
     * @param  callable(string, object): mixed  $preview
     */
    public function preview(callable $preview): static
    {
        $this->preview = $preview;

        return $this;
    }

    /**
     * Check if notification has a preview.
     */
    public function hasPreview(Channel $channel, object $notifiable): bool
    {
        return (bool) $this->getPreview($channel, $notifiable);
    }

    /**
     * Get subscription groups.
     */
    public function getGroups(): Groups
    {
        return new Groups($this->groups ?: [Group::fallback()]);
    }

    /**
     * Get notification class name.
     *
     * @return class-string<Notification>
     */
    public function getNotification(): string
    {
        return $this->notification;
    }

    /**
     * Get subscription description.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Get notification preview.
     */
    public function getPreview(string|Channel $channel, object $notifiable): mixed
    {
        if ($channel instanceof Channel) {
            $channel = $channel->getName();
        }

        return is_callable($this->preview) ? call_user_func($this->preview, $channel, $notifiable) : null;
    }

    public function toArray(): array
    {
        return [
            'groups'       => $this->getGroups(),
            'notification' => $this->getNotification(),
            'title'        => $this->getTitle(),
            'description'  => $this->getDescription(),
            'channels'     => $this->getChannels()->toArray(),
            'varieties'    => $this->getVarieties(),
            //'preview'      => $this->hasPreview(),
        ];
    }
}
