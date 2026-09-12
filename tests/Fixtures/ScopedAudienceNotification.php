<?php

namespace Codewiser\Postie\Tests\Fixtures;

use Codewiser\Postie\Attributes\Audience;
use Illuminate\Notifications\Notification;

/**
 * Notification scoped for an audience that is not predefined.
 */
#[Audience('admins')]
class ScopedAudienceNotification extends Notification
{
}