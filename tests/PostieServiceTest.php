<?php

namespace Codewiser\Postie\Tests;

use Codewiser\Postie\Channel;
use Codewiser\Postie\Models\Preference;
use Codewiser\Postie\PostieService;
use Codewiser\Postie\Subscription;
use Codewiser\Postie\Tests\Fixtures\ExampleNotification;
use Codewiser\Postie\Tests\Models\User;
use Illuminate\Notifications\AnonymousNotifiable;

class PostieServiceTest extends TestCase
{
    private PostieService $postie;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->postie = app(PostieService::class);

        $this->user = User::create([
            'name'     => 'John Doe',
            'email'    => 'john@doe.com',
            'password' => 'secret',
        ]);

        PostieService::$subscriptions = function () {
            return [
                Subscription::to(ExampleNotification::class)
                    ->for(fn() => User::query()),
            ];
        };
    }

    public function test_via_returns_active_channels_without_preferences(): void
    {
        // 'mail' is active by default, 'telegram' is passive.
        $this->assertSame(['mail'], $this->postie->via(ExampleNotification::class, $this->user));
    }

    public function test_via_intersects_anonymous_notifiable_routes(): void
    {
        $notifiable = new AnonymousNotifiable;
        $notifiable->route('mail', 'a@mail.ru')->route('telegram', '@me');

        $this->assertSame(
            ['mail', 'telegram'],
            $this->postie->via(ExampleNotification::class, $notifiable)
        );
    }

    public function test_via_respects_user_preferences(): void
    {
        $this->postie->toggleUserPreferences($this->user, ExampleNotification::class, ['telegram' => true]);

        $this->assertSame(['mail', 'telegram'], $this->postie->via(ExampleNotification::class, $this->user));
    }

    public function test_via_drops_channels_user_unsubscribed_from(): void
    {
        $this->postie->toggleUserPreferences($this->user, ExampleNotification::class, ['mail' => false]);

        $this->assertSame([], $this->postie->via(ExampleNotification::class, $this->user));
    }

    public function test_via_keeps_broadcast_even_without_route(): void
    {
        PostieService::$subscriptions = [
            Subscription::to(ExampleNotification::class)
                ->via(['broadcast', 'mail', 'telegram']),
        ];

        $this->assertSame(
            ['broadcast', 'mail'],
            $this->postie->via(ExampleNotification::class, $this->user)
        );
    }

    public function test_via_ignores_preferences_of_forced_channel(): void
    {
        PostieService::$subscriptions = [
            Subscription::to(ExampleNotification::class)
                ->via([Channel::via('sms', default: false, forced: true)]),
        ];

        // User prefers sms, but the channel is forced to passive.
        $this->postie->toggleUserPreferences($this->user, ExampleNotification::class, ['sms' => true]);

        $this->assertSame([], $this->postie->via(ExampleNotification::class, $this->user));
    }

    public function test_via_filters_channels_without_route(): void
    {
        PostieService::$subscriptions = [
            Subscription::to(ExampleNotification::class)
                ->via(['mail', 'sms']),
        ];

        // User has a route for mail, but not for sms.
        $this->assertSame(['mail'], $this->postie->via(ExampleNotification::class, $this->user));
    }

    public function test_toggle_creates_preference_row(): void
    {
        $preference = $this->postie->toggleUserPreferences(
            $this->user,
            ExampleNotification::class,
            ['telegram' => true]
        );

        $this->assertTrue($preference->exists);
        $this->assertSame(ExampleNotification::class, $preference->notification);
        $this->assertSame(['telegram' => true], $preference->channels);
    }

    public function test_toggle_does_not_store_values_that_match_defaults(): void
    {
        $this->postie->toggleUserPreferences($this->user, ExampleNotification::class, ['mail' => true]);

        $this->assertDatabaseCount((new Preference)->getTable(), 0);
    }

    public function test_toggle_deletes_row_when_back_to_defaults(): void
    {
        $this->postie->toggleUserPreferences($this->user, ExampleNotification::class, ['telegram' => true]);
        $this->postie->toggleUserPreferences($this->user, ExampleNotification::class, ['telegram' => false]);

        $this->assertDatabaseCount((new Preference)->getTable(), 0);
    }
}