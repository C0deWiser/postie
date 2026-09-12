<?php

namespace Codewiser\Postie\Tests;

use Codewiser\Postie\PostieService;
use Codewiser\Postie\Subscription;
use Codewiser\Postie\Tests\Fixtures\ExampleNotification;
use Codewiser\Postie\Tests\Models\User;

class ChannelizationTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name'     => 'John Doe',
            'email'    => 'john@doe.com',
            'password' => 'secret',
        ]);

        PostieService::$subscriptions = [
            Subscription::to(ExampleNotification::class)
                ->for('everyone'),
        ];
    }

    public function test_notification_via_uses_user_preferences(): void
    {
        app(PostieService::class)->toggleUserPreferences(
            $this->user,
            ExampleNotification::class,
            ['telegram' => true]
        );

        $notification = new ExampleNotification;

        $this->assertSame(
            ['mail', 'telegram'],
            $notification->via($this->user)
        );
    }

    public function test_notification_via_uses_default_states_without_preferences(): void
    {
        $notification = new ExampleNotification;

        // 'telegram' is passive by default.
        $this->assertSame(['mail'], $notification->via($this->user));
    }
}