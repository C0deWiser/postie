<?php

namespace Codewiser\Postie\Tests;

use Codewiser\Postie\Channel;
use Codewiser\Postie\Group;
use Codewiser\Postie\Models\Preference;
use Codewiser\Postie\PostieService;
use Codewiser\Postie\Subscription;
use Codewiser\Postie\Tests\Fixtures\ExampleNotification;
use Codewiser\Postie\Tests\Fixtures\SecondExampleNotification;
use Codewiser\Postie\Tests\Models\User;

class ApiCrudTest extends TestCase
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
            Subscription::to(SecondExampleNotification::class)
                ->for('everyone'),
        ];
    }

    public function test_channels_index_lists_definitions(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('postie.channels.index'))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'mail')
            ->assertJsonPath('data.0.available', true)
            ->assertJsonPath('data.1.name', 'telegram')
            ->assertJsonPath('data.1.available', true);
    }

    public function test_channels_show(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('postie.channels.show', ['channel' => 'mail']))
            ->assertOk()
            ->assertJsonPath('data.name', 'mail')
            ->assertJsonPath('data.icon', 'bi bi-envelope')
            ->assertJsonPath('data.available', true);
    }

    public function test_channels_show_missing_returns_404(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('postie.channels.show', ['channel' => 'nope']))
            ->assertNotFound();
    }

    public function test_channels_unavailable_when_no_route(): void
    {
        PostieService::$channels = [
            Channel::via('sms'),
            Channel::via('broadcast'),
        ];

        $this->actingAs($this->user)
            ->getJson(route('postie.channels.index'))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'sms')
            ->assertJsonPath('data.0.available', false)
            ->assertJsonPath('data.1.name', 'broadcast')
            ->assertJsonPath('data.1.available', true);
    }

    public function test_groups_index_lists_groups_for_user(): void
    {
        PostieService::$groups = [
            Group::make('Daily')->weight(1),
        ];

        PostieService::$subscriptions = [
            Subscription::to(ExampleNotification::class)
                ->for('everyone')
                ->group('Daily'),
        ];

        $this->actingAs($this->user)
            ->getJson(route('postie.groups.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Daily');
    }

    public function test_groups_show_by_shortcode(): void
    {
        $group = Group::make('Daily')->weight(1);

        PostieService::$groups = [$group];

        PostieService::$subscriptions = [
            Subscription::to(ExampleNotification::class)
                ->for('everyone')
                ->group('Daily'),
        ];

        $this->actingAs($this->user)
            ->getJson(route('postie.groups.show', ['group' => $group->getShortcode()]))
            ->assertOk()
            ->assertJsonPath('data.name', 'Daily');
    }

    public function test_groups_show_missing_returns_404(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('postie.groups.show', ['group' => 'nope']))
            ->assertNotFound();
    }

    public function test_audiences_index_lists_definitions(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('postie.audiences.index'))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'everyone');
    }

    public function test_audiences_show(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('postie.audiences.show', ['audience' => 'everyone']))
            ->assertOk()
            ->assertJsonPath('data.name', 'everyone')
            ->assertJsonPath('data.title', 'Everyone');
    }

    public function test_audiences_show_missing_returns_404(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('postie.audiences.show', ['audience' => 'nope']))
            ->assertNotFound();
    }

    public function test_docs_page_served_at_collection_root(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('postie.api.docs'))
            ->assertOk()
            ->assertSee('redoc')
            ->assertSee('/postie/api/openapi.yaml');
    }

    public function test_openapi_spec_served(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('postie.api.openapi'));

        $response
            ->assertOk()
            ->assertHeader('content-type', 'text/yaml; charset=UTF-8');

        $this->assertNotFalse(str_contains($response->content(), 'openapi: 3.0.3'));
    }

    public function test_notifications_index_lists_subscriptions_for_user(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('postie.notifications.index'))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.notification', ExampleNotification::class)
            ->assertJsonPath('data.0.channels.0.name', 'mail')
            ->assertJsonPath('data.0.channels.0.status', true)
            ->assertJsonPath('data.1.notification', SecondExampleNotification::class);
    }

    public function test_notifications_index_filters_by_group_shortcode(): void
    {
        $group = Group::make('Daily')->weight(1);

        PostieService::$groups = [$group];

        PostieService::$subscriptions = [
            Subscription::to(ExampleNotification::class)
                ->for('everyone')
                ->group('Daily'),
            Subscription::to(SecondExampleNotification::class)
                ->for('everyone')
                ->group('Weekly'),
        ];

        $this->actingAs($this->user)
            ->getJson(route('postie.notifications.index', ['group' => $group->getShortcode()]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.notification', ExampleNotification::class);

        $this->actingAs($this->user)
            ->getJson(route('postie.notifications.index', ['group' => 'nope']))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_subscriptions_show(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('postie.notifications.show', ['notification' => ExampleNotification::class]))
            ->assertOk()
            ->assertJsonPath('data.notification', ExampleNotification::class)
            ->assertJsonPath('data.channels.0.name', 'mail')
            ->assertJsonPath('data.channels.0.status', true);
    }

    public function test_subscriptions_show_unknown_returns_404(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('postie.notifications.show', ['notification' => 'UnknownNotification']))
            ->assertNotFound();
    }

    public function test_preferences_subscribe_enables_channel(): void
    {
        $this->actingAs($this->user)
            ->putJson(
                route('postie.notifications.channels.subscribe', [
                    'notification' => ExampleNotification::class,
                    'channel'      => 'telegram',
                ])
            )
            ->assertOk()
            ->assertJsonPath('data.channels.0.name', 'mail')
            ->assertJsonPath('data.channels.0.status', true)
            ->assertJsonPath('data.channels.1.name', 'telegram')
            ->assertJsonPath('data.channels.1.status', true);

        $preference = Preference::for($this->user, ExampleNotification::class)->first();

        $this->assertSame(['telegram' => true], $preference->channels);
    }

    public function test_preferences_subscribe_is_noop_for_default_enabled_channel(): void
    {
        $this->actingAs($this->user)
            ->putJson(
                route('postie.notifications.channels.subscribe', [
                    'notification' => ExampleNotification::class,
                    'channel'      => 'mail',
                ])
            )
            ->assertOk()
            ->assertJsonPath('data.channels.0.name', 'mail')
            ->assertJsonPath('data.channels.0.status', true);

        $this->assertCount(0, Preference::for($this->user, ExampleNotification::class)->get());
    }

    public function test_preferences_subscribe_validates_channel(): void
    {
        $this->actingAs($this->user)
            ->putJson(
                route('postie.notifications.channels.subscribe', [
                    'notification' => ExampleNotification::class,
                    'channel'      => 'nope',
                ])
            )
            ->assertStatus(422)
            ->assertJsonValidationErrors('channel');
    }

    public function test_preferences_subscribe_unknown_subscription_returns_404(): void
    {
        $this->actingAs($this->user)
            ->putJson(
                route('postie.notifications.channels.subscribe', [
                    'notification' => 'UnknownNotification',
                    'channel'      => 'mail',
                ])
            )
            ->assertNotFound();
    }

    public function test_preferences_unsubscribe_disables_channel(): void
    {
        $this->actingAs($this->user)
            ->deleteJson(
                route('postie.notifications.channels.unsubscribe', [
                    'notification' => ExampleNotification::class,
                    'channel'      => 'mail',
                ])
            )
            ->assertOk()
            ->assertJsonPath('data.channels.0.name', 'mail')
            ->assertJsonPath('data.channels.0.status', false)
            ->assertJsonPath('data.channels.1.name', 'telegram')
            ->assertJsonPath('data.channels.1.status', false);

        $preference = Preference::for($this->user, ExampleNotification::class)->first();

        $this->assertSame(['mail' => false], $preference->channels);
    }

    public function test_preferences_unsubscribe_unknown_subscription_returns_404(): void
    {
        $this->actingAs($this->user)
            ->deleteJson(
                route('postie.notifications.channels.unsubscribe', [
                    'notification' => 'UnknownNotification',
                    'channel'      => 'mail',
                ])
            )
            ->assertNotFound();
    }
}