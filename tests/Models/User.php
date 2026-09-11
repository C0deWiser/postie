<?php

namespace Codewiser\Postie\Tests\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $guarded = [];

    public function routeNotificationFor($driver, $notification = null)
    {
        return match ($driver) {
            'mail'     => $this->email,
            'telegram' => $this->name,
            default    => null,
        };
    }
}