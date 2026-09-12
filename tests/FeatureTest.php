<?php

namespace Codewiser\Postie\Tests;

use Codewiser\Postie\PostieService;
use Codewiser\Postie\Models\Preference;
use Codewiser\Postie\Subscription;
use Codewiser\Postie\Tests\Fixtures\ExampleNotification;
use Codewiser\Postie\Tests\Models\User;

class FeatureTest extends TestCase
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

        PostieService::$subscriptions = [
            Subscription::to(ExampleNotification::class)
->for('everyone')
                ->preview(fn(string $channel) => 'Preview for '.$channel),
        ];
    }

    public function test_api_requires_authentication(): void
    {
        $this->get(route('postie.subscriptions.index'))
            ->assertStatus(302);

        $this->post(route('postie.subscriptions.toggle'), [
            'notification' => ExampleNotification::class,
            'channels'     => ['mail' => false],
        ])->assertStatus(302);
    }

    public function test_preview_requires_authentication(): void
    {
        $this->get(route('postie.preview', [
            'channel'      => 'mail',
            'notification' => ExampleNotification::class,
        ]))->assertStatus(302);
    }

    public function test_subscriptions_index_lists_user_subscriptions(): void
    {
        $this->actingAs($this->user)
            ->get(route('postie.subscriptions.index'))
            ->assertOk()
            ->assertJsonPath('subscriptions.0.notification', ExampleNotification::class)
            ->assertJsonPath('subscriptions.0.channels.0.name', 'mail')
            ->assertJsonPath('subscriptions.0.channels.0.status', true)
            ->assertJsonPath('subscriptions.0.channels.0.available', true);
    }

    public function test_subscriptions_index_filters_by_group(): void
    {
        PostieService::$subscriptions = [
            Subscription::to(ExampleNotification::class)->for('everyone')->group('Group 1'),
        ];

        $group = new \Codewiser\Postie\Group('Group 1');

        $this->actingAs($this->user)
            ->get(route('postie.subscriptions.index').'?group='.$group->getShortcode())
            ->assertOk()
            ->assertJsonCount(1, 'subscriptions');

        $this->actingAs($this->user)
            ->get(route('postie.subscriptions.index').'?group=nope')
            ->assertOk()
            ->assertJsonCount(0, 'subscriptions');
    }

    public function test_subscriptions_toggle_updates_preferences(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('postie.subscriptions.toggle'), [
                'notification' => ExampleNotification::class,
                'channels'     => ['mail' => false, 'telegram' => true],
            ])
            ->assertCreated()
            ->assertJsonPath('data.notification', ExampleNotification::class)
            ->assertJsonPath('data.channels.mail', false)
            ->assertJsonPath('data.channels.telegram', true);

        $preference = Preference::for($this->user, ExampleNotification::class)->first();

        $this->assertSame(['mail' => false, 'telegram' => true], $preference->channels);
    }

    public function test_subscriptions_toggle_validates_notification(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('postie.subscriptions.toggle'), [
                'notification' => 'UnknownNotification',
                'channels'     => ['mail' => false],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('notification');
    }

    public function test_subscriptions_toggle_validates_channels(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('postie.subscriptions.toggle'), [
                'notification' => ExampleNotification::class,
                'channels'     => 'not-array',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('channels');
    }

    public function test_preview_renders_content(): void
    {
        $this->actingAs($this->user)
            ->get(route('postie.preview', [
                'channel'      => 'mail',
                'notification' => ExampleNotification::class,
            ]))
            ->assertOk()
            ->assertSee('Preview for mail');
    }

    public function test_toggle_is_scoped_to_user(): void
    {
        $otherUser = User::create([
            'name'     => 'Jane Doe',
            'email'    => 'jane@doe.com',
            'password' => 'secret',
        ]);

        $this->actingAs($this->user)
            ->post(route('postie.subscriptions.toggle'), [
                'notification' => ExampleNotification::class,
                'channels'     => ['mail' => false],
            ])
            ->assertCreated();

        $this->assertCount(1, Preference::for($this->user, ExampleNotification::class)->get());
        $this->assertCount(0, Preference::for($otherUser, ExampleNotification::class)->get());
    }
}