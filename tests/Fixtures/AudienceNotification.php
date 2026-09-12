<?php

namespace Codewiser\Postie\Tests\Fixtures;

use Codewiser\Postie\Attributes\Audience;
use Illuminate\Notifications\Notification;

/**
 * Notification scoped for a predefined audience ('customers').
 */
#[Audience(Audiences::Customers)]
class AudienceNotification extends Notification
{
}