<?php

namespace Codewiser\Postie\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

/**
 * User unsubscribed from a channel.
 */
class UserUnsubscribe
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  Model  $notifiable
     * @param  class-string<Notification>  $notification
     * @param  string  $channel
     */
    public function __construct(public Model $notifiable, public string $notification, public string $channel)
    {
        //
    }
}
