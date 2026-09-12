<?php

namespace Codewiser\Postie\Tests\App;

use Codewiser\Postie\Audience;
use Codewiser\Postie\Channel;
use Codewiser\Postie\Group;
use Codewiser\Postie\PostieApplicationServiceProvider;
use Codewiser\Postie\Subscription;
use Codewiser\Postie\Tests\Fixtures\Audiences;
use Codewiser\Postie\Tests\Fixtures\GroupedNotification;
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
                ->for('everyone'),
        ];
    }

    public function audiences(): array
    {
        return [
            Audience::make('everyone', 'Everyone')
                ->with(fn() => \Codewiser\Postie\Tests\Models\User::query()),
            Audience::make(Audiences::Customers, 'Customers')
                ->with(fn() => \Codewiser\Postie\Tests\Models\User::query()),
        ];
    }

    public function notifications(): array
    {
        return [
            Subscription::to(ExampleNotification::class),
            // A notification class name is wrapped into a Subscription.
            GroupedNotification::class,
            Group::make('Group')
                ->icon('steam')
                ->via(['telegram'])
                ->add(Subscription::to(PlainNotification::class)),
            Subscription::to(SecondExampleNotification::class)
                ->group('Group'),
        ];
    }
}