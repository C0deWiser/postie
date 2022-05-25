<?php

namespace Codewiser\Postie;

use Closure;
use Codewiser\Postie\Collections\ChannelCollection;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class NotificationDefinition implements Arrayable
{
    protected string $class_name;
    protected Closure $audienceBuilder;
    protected array $channels;
    protected string $title;

    /**
     * Make definition using notification class name.
     *
     * @param string $notification
     * @return NotificationDefinition
     */
    public static function make(string $notification): NotificationDefinition
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
    public function getAudienceBuilder(): Builder
    {
        return call_user_func($this->audienceBuilder);
    }

    /**
     * Get notification available channels.
     *
     * @return ChannelCollection
     */
    public function getChannels(): ChannelCollection
    {
        return ChannelCollection::make($this->channels);
    }

    /**
     * Get notification description.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Define Builder that holds notification possible audience. Closure should return Eloquent Builder.
     */
    public function audience(Closure $audienceBuilder): self
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
     * @param array<ChannelDefinition> $channels
     * @return $this
     */
    public function channels(array $channels): self
    {
        $this->channels = $channels;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'notification' => $this->class_name,
            'title' => $this->title,
            'channels' => $this->getChannels()->toArray(),
        ];
    }


    /**
     * Get notification channels using user preferences.
     */
    public function getUserChannels(array $userChannels = []): array
    {
        return $this->getChannels()
            ->mapWithKeys(function (ChannelDefinition $channelDefinition) use ($userChannels) {
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
            ->map(function (ChannelDefinition $channelDefinition) {
                return $channelDefinition->getName();
            })
            ->toArray();
    }
}
