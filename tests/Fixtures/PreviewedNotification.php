<?php

namespace Codewiser\Postie\Tests\Fixtures;

use Codewiser\Postie\Attributes\Preview;
use Illuminate\Notifications\Notification;

class PreviewedNotification extends Notification
{
    #[Preview]
    public static function preview(): callable
    {
        return fn(string $channel, object $notifiable) => "Attribute preview for $channel";
    }
}