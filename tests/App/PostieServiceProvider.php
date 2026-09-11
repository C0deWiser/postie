<?php

namespace Codewiser\Postie\Tests\App;

use Codewiser\Postie\Channel;
use Codewiser\Postie\Group;
use Codewiser\Postie\PostieApplicationServiceProvider;
use Codewiser\Postie\Subscription;
use Codewiser\Postie\Tests\Fixtures\PlainNotification;
use Codewiser\Postie\Tests\Fixtures\ExampleNotification;
use Codewiser\Postie\Tests\Fixtures\SecondExampleNotification;

class PostieServiceProvider extends PostieApplicationServiceProvider
{
    public function channels(): array
    {
        return [
            Channel::via('mail')->title('E-mail')->icon('envelope'),
            Channel::via('telegram')->title('Telegram')->icon('telegram')->passive(),
        ];
    }

    public function groups(): array
    {
        return [
            Group::make('Daily')
                ->icon('broadcast')
                ->weight(3)
                ->via(['mail'])
                ->for(fn() => \Codewiser\Postie\Tests\Models\User::query()),
        ];
    }

    public function notifications(): array
    {
        return [
            Subscription::to(ExampleNotification::class),
            Group::make('Group')
                ->icon('steam')
                ->via(['telegram'])
                ->add(Subscription::to(PlainNotification::class)),
            Subscription::to(SecondExampleNotification::class)
                ->group('Group'),
        ];
    }
}