<?php

namespace Codewiser\Postie\Attributes;

/**
 * Notification preview.
 *
 * May be applied to a static method of a Notification class.
 * Such method should return a callable
 * that will be called with 'channel' and 'notifiable' parameters.
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class Preview
{
    //
}