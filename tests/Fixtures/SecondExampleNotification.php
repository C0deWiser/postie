<?php

namespace Codewiser\Postie\Tests\Fixtures;

use Codewiser\Postie\Attributes\Channel;
use Codewiser\Postie\Attributes\Description;
use Codewiser\Postie\Attributes\Subject;
use Codewiser\Postie\Notifications\Traits\Channelization;
use Illuminate\Notifications\Notification;

#[Subject('Second one')]
#[Description('Summary of the latest activity in your workspace.')]
#[Channel('mail')]
class SecondExampleNotification extends Notification
{
    use Channelization;
}