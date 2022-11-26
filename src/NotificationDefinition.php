<?php

namespace Codewiser\Postie;

/**
 * @deprecated
 */
class NotificationDefinition extends Subscription
{
    /**
     * Make definition using notification class name.
     */
    public static function make(string $notification): NotificationDefinition
    {
        return new static($notification);
    }
}
