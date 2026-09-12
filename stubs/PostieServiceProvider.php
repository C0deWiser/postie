<?php

namespace App\Providers;

use Codewiser\Postie\Audience;
use Codewiser\Postie\Channel;
use Codewiser\Postie\Group;
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
     * Return an array of Group definitions.
     *
     * @return array
     */
    public function groups(): array
    {
        return [
            Group::make('Group')
                ->icon('asterisk'),
        ];
    }

    /**
     * Return an array of Audience definitions.
     *
     * @return array
     */
    public function audiences(): array
    {
        return [
            Audience::make('admins', 'Administration')
                ->with(fn() => User::query()->where('role', 'admin'))
        ];
    }

    /**
     * Return an array of Subscription definitions.
     *
     * @return array
     */
    public function notifications(): array
    {
        return [
            Subscription::to('App\Notifications\MyNotification')
                ->title('Digest')
                ->group('Group')
                ->for('admins')
                ->via([
                    'mail',
                    'telegram'
                ])
        ];
    }
}
