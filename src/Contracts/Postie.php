<?php

namespace Codewiser\Postie\Contracts;

use Closure;
use Codewiser\Postie\Collections\NotificationCollection;
use Codewiser\Postie\Models\Subscription;
use Codewiser\Postie\NotificationDefinition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notifiable;
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
     * Send notification to audience. Use callback to modify audience builder.
     *
     * Or send notification to the exact notifiable, passed by second argument.
     *
     * @param Notification $notification
     * @param mixed $closure
     * @return void
     */
    public function send(Notification $notification, $closure = null);
}
