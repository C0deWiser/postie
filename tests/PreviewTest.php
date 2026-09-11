<?php

namespace Codewiser\Postie\Tests;

use Codewiser\Postie\Channel;
use Codewiser\Postie\PostieService;
use Codewiser\Postie\Subscription;
use Codewiser\Postie\Tests\Fixtures\ExampleNotification;
use Codewiser\Postie\Tests\Fixtures\PreviewedNotification;
use Codewiser\Postie\Tests\Models\User;

class PreviewTest extends TestCase
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
    }

    public function test_preview_returns_content(): void
    {
        $subscription = Subscription::to(ExampleNotification::class)
            ->preview(fn(string $channel, object $notifiable) => "Preview for $channel");

        $this->assertSame('Preview for mail', $subscription->getPreview('mail', $this->user));
        $this->assertSame('Preview for telegram', $subscription->getPreview('telegram', $this->user));
    }

    public function test_preview_accepts_channel_object(): void
    {
        $subscription = Subscription::to(ExampleNotification::class)
            ->preview(fn(string $channel, object $notifiable) => "Preview for $channel");

        $this->assertSame('Preview for mail', $subscription->getPreview(Channel::via('mail'), $this->user));
    }

    public function test_has_preview(): void
    {
        $subscription = Subscription::to(ExampleNotification::class)
            ->preview(fn(string $channel, object $notifiable) => 'Preview');

        $this->assertTrue($subscription->hasPreview(Channel::via('mail'), $this->user));
    }

    public function test_preview_attribute_uses_static_callable(): void
    {
        $subscription = Subscription::to(PreviewedNotification::class);

        $this->assertSame('Attribute preview for mail', $subscription->getPreview('mail', $this->user));
        $this->assertSame('Attribute preview for telegram', $subscription->getPreview('telegram', $this->user));
        $this->assertTrue($subscription->hasPreview(Channel::via('mail'), $this->user));
    }

    public function test_fluent_preview_overrides_attribute(): void
    {
        $subscription = Subscription::to(PreviewedNotification::class)
            ->preview(fn(string $channel, object $notifiable) => "Fluent preview for $channel");

        $this->assertSame('Fluent preview for mail', $subscription->getPreview('mail', $this->user));
        $this->assertTrue($subscription->hasPreview(Channel::via('mail'), $this->user));
    }

    public function test_attribute_without_static_method_returns_null(): void
    {
        $subscription = Subscription::to(ExampleNotification::class);

        $this->assertFalse($subscription->hasPreview(Channel::via('mail'), $this->user));
        $this->assertNull($subscription->getPreview('mail', $this->user));
    }

    public function test_missing_preview_returns_false_and_null(): void
    {
        $subscription = Subscription::to(ExampleNotification::class);

        $this->assertFalse($subscription->hasPreview(Channel::via('mail'), $this->user));
        $this->assertNull($subscription->getPreview('mail', $this->user));
    }

    public function test_controller_renders_preview(): void
    {
        PostieService::$subscriptions = [
            Subscription::to(ExampleNotification::class)
                ->preview(fn(string $channel, object $notifiable) => '<b>Preview for '.$channel.'</b>'),
        ];

        $this->actingAs($this->user)
            ->get(route('postie.preview', [
                'channel'      => 'mail',
                'notification' => ExampleNotification::class,
            ]))
            ->assertOk()
            ->assertSee('<b>Preview for mail</b>', false);
    }

    public function test_controller_404_for_unknown_notification(): void
    {
        PostieService::$subscriptions = [
            Subscription::to(ExampleNotification::class),
        ];

        $this->actingAs($this->user)
            ->get(route('postie.preview', [
                'channel'      => 'mail',
                'notification' => 'UnknownNotification',
            ]))
            ->assertNotFound();
    }

    public function test_controller_404_for_unknown_channel(): void
    {
        PostieService::$subscriptions = [
            Subscription::to(ExampleNotification::class),
        ];

        $this->actingAs($this->user)
            ->get(route('postie.preview', [
                'channel'      => 'unknown',
                'notification' => ExampleNotification::class,
            ]))
            ->assertNotFound();
    }

    public function test_controller_404_without_configured_preview(): void
    {
        PostieService::$subscriptions = [
            Subscription::to(ExampleNotification::class),
        ];

        $this->actingAs($this->user)
            ->get(route('postie.preview', [
                'channel'      => 'mail',
                'notification' => ExampleNotification::class,
            ]))
            ->assertNotFound();
    }
}