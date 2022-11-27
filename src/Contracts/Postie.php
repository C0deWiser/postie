<?php

namespace Codewiser\Postie\Contracts;

use Codewiser\Postie\Collections\NotificationCollection;
use Codewiser\Postie\Models\Subscription;
use Illuminate\Notifications\Notification;

interface Postie
{
    /**
     * Get collection of notification definitions.
     *
     * @return NotificationCollection
     */
    public function getNotifications(): NotificationCollection;

    /**
     * Get notification channels based on notifiable preferences.
     *
     * @param string $notification
     * @param mixed $notifiable
     * @return array
     */
    public function via(string $notification, $notifiable): array;

    /**
     * Get notifications available to given notifiable.
     * 
     * @param mixed $notifiable
     */
    public function getUserNotifications($notifiable): array;

    /**
     * Save user preferences.
     *
     * @param mixed $notifiable
     * @param string $notification
     * @param array $channels Channels preferences (['mail' => true])
     * @return Subscription
     */
    public function toggleUserNotificationChannels($notifiable, string $notification, array $channels): Subscription;

    /**
     * Send notification to the subscribed audience.
     * Use callback to modify audience Builder.
     * Callback may return Builder or LazyCollection.
     *
     * If notification is not registered in Postie, second argument is required.
     *
     * @param Notification $notification
     * @param callable|null $callback
     */
    public function send(Notification $notification, callable $callback = null);
}
