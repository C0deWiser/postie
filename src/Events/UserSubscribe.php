<?php

namespace Codewiser\Postie\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

/**
 * User subscribed to a channel.
 */
class UserSubscribe
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
