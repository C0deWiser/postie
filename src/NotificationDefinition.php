<?php

namespace Codewiser\Postie;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Определение оповещения
 */
class NotificationDefinition implements Arrayable
{
    protected string $notification;
    protected \Closure $audienceBuilder;
    protected array $channels;
    protected string $title;

    public static function make(string $notification): NotificationDefinition
    {
        return new static($notification);
    }

    public function __construct(string $notification)
    {
        $this->notification = $notification;
        $this->title = (string)Str::of(class_basename($notification))->snake()->studly();
    }

    /**
     * Оповещение
     */
    public function getNotification(): string
    {
        return $this->notification;
    }

    /**
     * Builder аудитории получающей оповещения
     * @return Builder
     */
    public function getAudienceBuilder(): Builder
    {
        return call_user_func($this->audienceBuilder);
    }

    /**
     * Массив определений каналов оповещения
     * @return array|ChannelDefinition[]
     */
    public function getChannels(): Collection
    {
        return collect($this->channels);
    }

    /**
     * Отображаемое название оповещения
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }


    /**
     * Установка builder'а для аудитории получателей оповещения
     *
     * @param \Closure $builder
     * @return $this
     */
    public function audienceBuilder(\Closure $audienceBuilder): self
    {
        $this->audienceBuilder = $audienceBuilder;
        return $this;
    }

    /**
     * Установка отображаемого названия оповещения
     *
     * @param string $title Название
     * @return $this
     */
    public function title(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Установка массива определений каналов
     * @param array|ChannelDefinition[] $channels Массив определений каналов
     * @return $this
     */
    public function channels(array $channels): self
    {
        $this->channels = $channels;
        return $this;
    }

    public function toArray()
    {
        return [
            'notification' => $this->notification,
            'title' => $this->title,
            'channels' => $this->getChannels()->toArray(),
        ];
    }


    /**
     * Возвращает массив актуальный массив каналов исходя из настроек и записи каналов подписки пользователя
     *
     * @param array $userChannels Каналы пользователя (из записи БД)
     * @return array
     */
    public function getActualChannelsWithStatus(array $userChannels = [], bool $withHidden = true): array
    {

        $result = [];
        $this->getChannels()->each(function (ChannelDefinition $channelDefinition) use ($userChannels, $withHidden, &$result) {
            // Если скрытые каналы не нужно показаывать, то и не показываем
            if (!$withHidden && $channelDefinition->getHidden()) {
                return false;
            }

            $result[$channelDefinition->getName()] =
                $channelDefinition->getForced()
                    ? $channelDefinition->getDefault()
                    : (
                        count($userChannels) && array_key_exists($channelDefinition->getName(), $userChannels)
                            ? $userChannels[$channelDefinition->getName()]
                            : $channelDefinition->getDefault()
                        );
        });
        return $result;
    }


    /**
     * Возвращает массив каналов для фронтенда
     * @return array
     */
    public function getFrontendChannels(): array
    {
        $result = [];
        foreach ($this->channels as $channel) {
            if (!$channel->hidden) {
                $result[] = [
                    'name' => $channel->name,
                    'title' => $channel->title,
                    'icon' => $channel->icon,
                ];
            }
        }
        return $result;
    }


    /**
     * Возвращает название класса оповещения без namespace'а
     *
     * @return string
     */
    public function getNotificationClassNameWithoutNamespace(): string
    {
        $path = explode('\\', $this->notification);
        return array_pop($path);
    }
}