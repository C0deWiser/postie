<?php

namespace Codewiser\Postie\Tests\Fixtures;

use Codewiser\Postie\Attributes\Group;
use Illuminate\Notifications\Notification;

#[Group('Group')]
class GroupedNotification extends Notification
{
}