<?php

namespace Codewiser\Postie\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Queue\SerializesModels;

/**
 * User unsubscribed from a channel.
 */
class UserUnsubscribe
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Notifiable.
     *
     * @var Authenticatable|Notifiable
     */
    public $notifiable;

    /**
     * Notification class name.
     *
     * @var string
     */
    public string $notification;

    /**
     * Channel name.
     *
     * @var string
     */
    public string $channel;

    public function __construct($notifiable, string $notification, string $channel)
    {
        $this->notifiable = $notifiable;
        $this->notification = $notification;
        $this->channel = $channel;
    }
}
