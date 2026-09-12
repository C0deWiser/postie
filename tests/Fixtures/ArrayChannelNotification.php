<?php

namespace Codewiser\Postie\Tests\Fixtures;

use Codewiser\Postie\Attributes\Channels;
use Illuminate\Notifications\Notification;

#[Channels(['mail', 'telegram'], default: true)]
class ArrayChannelNotification extends Notification
{
}