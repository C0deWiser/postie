<?php

namespace Codewiser\Postie;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;

/**
 * Определение канала оповещения
 */
class ChannelDefinition implements Arrayable
{
    protected string $name;
    protected string $title;
    protected bool $default = false;
    protected bool $forced = false;
    protected bool $hidden = false;
    protected string $icon;

    /**
     * Стартовый метод идентификации канала
     *
     * @param string $name Индекс канала (значение, которое используется в массиве via оповещений)
     * @return ChannelDefinition
     */
    public static function make(string $name): ChannelDefinition
    {
        return new static($name);
    }

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->title = (string)Str::of(class_basename($name))->snake()->studly();

        switch ($name) {
            case 'mail':
                $this->icon = 'bi bi-envelope-fill';
                break;
            case 'database':
                $this->icon = 'bi bi-hdd-fill';
                break;
            default:
                $this->icon = 'bi bi-record-circle-fill';
                break;
        }
    }

    /**
     * Имя канала (для массива via в оповещении)
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Отображаемое название канала
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Дефолтное значение флага используемости канала
     * @return bool
     */
    public function getDefault(): bool
    {
        return $this->default;
    }

    /**
     * Дефолтное значение флага используемости канала
     * @return bool
     */
    public function getForced(): bool
    {
        return $this->forced;
    }

    /**
     * Флаг скрытия канала на фронтенде
     * @return bool
     */
    public function getHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * Класс bootstrap иконки
     * @return string
     */
    public function getIcon(): string
    {
        return $this->icon;
    }

    /**
     * Отображаемое название
     *
     * @param string $title
     * @return $this
     */
    public function title(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Значение по-умолчанию
     *
     * @param bool $default
     * @return $this
     */
    public function default(bool $default): self
    {
        $this->default = $default;
        return $this;
    }

    /**
     * Флаг неизменяемости дефолтного значения
     *
     * @param bool $forced (default true)
     * @return $this
     */
    public function forced(bool $forced = true): self
    {
        $this->forced = $forced;
        return $this;
    }

    /**
     * Скрыть канал в интерфейсе (default false)
     *
     * @param bool $hidden
     * @return $this
     */
    public function hidden(bool $hidden = true): self
    {
        $this->hidden = $hidden;
        return $this;
    }

    /**
     * Установка названия класса bootstrap-icon (без префикса "bi bi-*")
     *
     * @param string $icon
     * @return $this
     *
     * @see https://icons.getbootstrap.com/
     */
    public function icon(string $icon): self
    {
        $this->icon = 'bi bi-'.$icon;
        return $this;
    }

    /**
     * Вычисляемое значение статуса канала оповещения
     *
     * @return bool
     */
    public function getStatus(bool $userChannelStatus = null): bool
    {
        if ($this->forced || is_null($userChannelStatus)) {
            return $this->default;
        }

        return $userChannelStatus;
    }

    public function toArray()
    {
        return [
            'name' => $this->name,
            'title' => $this->title,
            'default' => $this->default,
            'forced' => $this->forced,
            'hidden' => $this->hidden,
            'icon' => $this->icon,
        ];
    }
}