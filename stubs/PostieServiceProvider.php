<?php

namespace App\Providers;

use Codewiser\Postie\Channel;
use Codewiser\Postie\Subscription;
use Codewiser\Postie\PostieApplicationServiceProvider;
use Illuminate\Foundation\Auth\User;

class PostieServiceProvider extends PostieApplicationServiceProvider
{
    /**
     * Return an array of Channel definitions.
     *
     * @return array
     */
    public function channels(): array
    {
        return [
            Channel::via('mail')
                ->title('E-mail')
                ->icon('envelope'),

            Channel::via('telegram')
                ->title('Telegram')
                ->icon('telegram')
                ->passive(),
        ];
    }

    /**
     * Return an array of NotificationDefinition
     *
     * @return array
     */
    public function notifications(): array
    {
        return [
            Subscription::to('App\Notifications\MyNotification')
                ->title('Digest')
                ->for(fn() => User::query())
                ->via([
                    'mail',
                    'telegram'
                ])
        ];
    }
}
