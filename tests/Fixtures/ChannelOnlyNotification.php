<?php

namespace Codewiser\Postie\Tests\Fixtures;

use Codewiser\Postie\Attributes\Channel;
use Illuminate\Notifications\Notification;

#[Channel('mail')]
class ChannelOnlyNotification extends Notification
{
}