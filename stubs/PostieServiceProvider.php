<?php

namespace App\Providers;

use Codewiser\Postie\ChannelDefinition;
use Codewiser\Postie\NotificationDefinition;
use Codewiser\Postie\PostieApplicationServiceProvider;
use Illuminate\Foundation\Auth\User;

class PostieServiceProvider extends PostieApplicationServiceProvider
{
    /**
     * Return an array of NotificationDefinition
     *
     * @return array
     */
    public function notifications(): array
    {
        return [
            NotificationDefinition::make('App\Notifications\MyNotification')
                ->title('Digest')
                ->audience(fn() => User::query())
                ->via(['mail'])
        ];
    }
}
