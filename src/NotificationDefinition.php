<?php

namespace Codewiser\Postie;

/**
 * @deprecated
 */
class NotificationDefinition extends Subscription
{
    /**
     * Make definition using notification class name.
     *
     * @param string $notification
     * @return Subscription
     */
    public static function make(string $notification): Subscription
    {
        return new static($notification);
    }
}
