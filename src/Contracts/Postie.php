<?php

namespace Codewiser\Postie\Contracts;

use Codewiser\Postie\Models\Contracts\Subscriptionable;
use Codewiser\Postie\Models\Subscription;
use Codewiser\Postie\NotificationDefinition;
use Illuminate\Support\Collection;

interface Postie
{
    /**
     * Массив определений оповещений
     *
     * @return Collection|NotificationDefinition[]
     */
    public function notificationDefinitions(): Collection;

    /**
     * Попытка найти определение оповещения по его индексу
     *
     * @param string $notification Оповещение
     * @return NotificationDefinition|null
     */
    public function findNotificationDefinitionByNotification(string $notification): ?NotificationDefinition;

    /**
     * @param string $notification
     * @param mixed $notifiable
     * @return array
     */
    public function via(string $notification, $notifiable): array;

    /**
     * Возвращает массив имен определнных оповещений
     *
     * @return array
     */
    public function getNotificationNames(): array;

    /**
     * Возвращает массив оповещений пользователя с актуальным массивом каналов оповещения
     * 
     * @return array
     */
    public function getUserNotifications(int $userId): array;

    /**
     * Установить статусы каналов оповещения пользователя
     *
     * @param int $userId ID Пользователя
     * @param string $notification Оповещение
     * @param array $channels Массив каналов со статусами (['mail' => true])
     * @return Subscription Модель правила оповещения
     */
    public function toggleUserNotificationChannels(int $userId, string $notification, array $channels): Subscription;
}