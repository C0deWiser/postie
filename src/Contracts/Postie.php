<?php

namespace Codewiser\Postie\Contracts;

use Codewiser\Postie\Models\Contracts\Subscriptionable;
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
}