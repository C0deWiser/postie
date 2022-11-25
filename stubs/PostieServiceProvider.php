<?php

namespace App\Providers;

use Codewiser\Postie\Channel;
use Codewiser\Postie\Subscription;
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
            Subscription::to('App\Notifications\MyNotification')
                ->title('Digest')
                ->for(fn() => User::query())
                ->via([
                    'mail',
                    Channel::via('telegram')
                        ->passive()
                ])
        ];
    }
}
