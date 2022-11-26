<?php

namespace Codewiser\Postie;

use Closure;
use Codewiser\Postie\Collections\ChannelCollection;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class Subscription implements Arrayable
{
    protected string $class_name;
    protected ?Closure $audienceBuilder = null;
    protected array $channels = [];
    protected string $title;
    protected ?Closure $preview = null;

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
     * Get Builder that holds notification audience.
     */
    public function getAudienceBuilder(): ?Builder
    {
        return $this->audienceBuilder ? call_user_func($this->audienceBuilder) : null;
    }

    /**
     * Get notification available channels.
     */
    public function getChannels(): ChannelCollection
    {
        return ChannelCollection::make($this->channels ?? []);
    }

    /**
     * Get notification description.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Get notification for previewing.
     */
    public function getNotificationForPreviewing(string $channel, User $notifiable): ?Notification
    {
        return $this->preview ? call_user_func($this->preview, $channel, $notifiable) : null;
    }

    /**
     * Check if previewing notification is defined.
     */
    public function hasNotificationForPreviewing(): bool
    {
        return !is_null($this->preview);
    }

    /**
     * Define Builder that holds notification possible audience. Closure should return Eloquent Builder.
     *
     * @deprecated
     */
    public function audience(Closure $audienceBuilder): self
    {
        return $this->for($audienceBuilder);
    }

    /**
     * Define Builder that holds notification possible audience. Closure should return Eloquent Builder.
     */
    public function for(Closure $audienceBuilder): self
    {
        $this->audienceBuilder = $audienceBuilder;
        return $this;
    }

    /**
     * Set notification human readable description.
     */
    public function title(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Set notification available channels.
     *
     * @param Channel|string|array $channels
     */
    public function via($channels): self
    {
        if (!is_array($channels)) {
            $channels = func_get_args();
        }

        foreach ($channels as $i => $channel) {
            if (is_string($channels)) {
                $channels[$i] = new Channel($channel);
            }
        }

        $this->channels = $channels;

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
            'notification' => $this->getClassName(),
            'title' => $this->getTitle(),
            'channels' => $this->getChannels()->toArray(),
            'preview' => $this->hasNotificationForPreviewing(),
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
}
