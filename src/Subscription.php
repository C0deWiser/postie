<?php

namespace Codewiser\Postie;

use Closure;
use Codewiser\Postie\Traits\HasAudience;
use Codewiser\Postie\Traits\HasChannels;
use Codewiser\Postie\Traits\HasTitle;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class Subscription implements Arrayable
{
    use HasChannels, HasAudience, HasTitle;

    protected string $class_name;
    protected ?string $description = null;
    protected ?Closure $preview = null;
    protected ?Group $group = null;

    /**
     * Make definition using notification class name.
     *
     * @param string $notification
     * @return Subscription
     */
    public static function to(string $notification): Subscription
    {
        return new static($notification);
    }

    /**
     * @param string $notification notification class name.
     */
    public function __construct(string $notification)
    {
        $this->class_name = $notification;
        $this->title = (string)Str::of(class_basename($notification))->snake()->studly();
    }

    /**
     * Get notification class name.
     */
    public function getClassName(): string
    {
        return $this->class_name;
    }

    /**
     * Get notification description.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Get notification for previewing.
     *
     * @return Notification|Mailable|array|mixed
     */
    public function getPreview(string $channel, User $notifiable)
    {
        return is_callable($this->preview) ? call_user_func($this->preview, $channel, $notifiable) : null;
    }

    /**
     * Check if previewing notification is defined.
     */
    public function hasPreview(): bool
    {
        return is_callable($this->preview);
    }

    /**
     * Set notification human readable description.
     */
    public function description(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Set notification for previewing.
     *
     * Closure will get $channel (string) and $notifiable (authenticatable) parameters.
     */
    public function preview(Closure $notification): self
    {
        $this->preview = $notification;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'group' => $this->getGroup() ? $this->getGroup()->toArray() : null,
            'notification' => $this->getClassName(),
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'channels' => $this->getChannels()->toArray(),
            'preview' => $this->hasPreview(),
        ];
    }


    /**
     * Get notification channels using user preferences.
     */
    public function getUserChannels(array $userChannels = []): array
    {
        return $this->getChannels()
            ->mapWithKeys(function (Channel $channelDefinition) use ($userChannels) {
                return [
                    $channelDefinition->getName() => $channelDefinition->getForced()
                        ? $channelDefinition->getDefault()
                        : (array_key_exists($channelDefinition->getName(), $userChannels)
                            ? $userChannels[$channelDefinition->getName()]
                            : $channelDefinition->getDefault()
                        )
                ];
            })
            ->toArray();
    }

    /**
     * Get notification channels names.
     */
    public function getChannelNames(): array
    {
        return $this->getChannels()
            ->map(function (Channel $channelDefinition) {
                return $channelDefinition->getName();
            })
            ->toArray();
    }

    /**
     * Get group definition.
     */
    public function getGroup(): ?Group
    {
        return $this->group;
    }

    /**
     * Group subscription.
     *
     * @param string|Group $group
     */
    public function group($group): self
    {
        $this->group = $group instanceof Group ? $group : new Group($group);

        return $this;
    }
}
