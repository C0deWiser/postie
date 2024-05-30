<?php

namespace Codewiser\Postie\Facades;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Facade;

/**
 * @method static send(Notification $notification, mixed $audience = null)
 */
class Postie extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Codewiser\Postie\Contracts\Postie::class;
    }
}
