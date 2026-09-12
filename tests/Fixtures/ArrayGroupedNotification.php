<?php

namespace Codewiser\Postie\Tests\Fixtures;

use Codewiser\Postie\Attributes\Groups;
use Illuminate\Notifications\Notification;

#[Groups(['Daily', 'Weekly'])]
class ArrayGroupedNotification extends Notification
{
}