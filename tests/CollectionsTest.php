<?php

namespace Codewiser\Postie\Tests;

use Codewiser\Postie\Channel;
use Codewiser\Postie\Collections\Channels;
use Codewiser\Postie\Collections\Groups;
use Codewiser\Postie\Collections\Subscriptions;
use Codewiser\Postie\Group;
use Codewiser\Postie\PostieService;
use Codewiser\Postie\Subscription;
use Codewiser\Postie\Tests\Fixtures\ExampleNotification;
use Codewiser\Postie\Tests\Fixtures\SecondExampleNotification;
use Codewiser\Postie\Tests\Models\User;
use Illuminate\Support\ItemNotFoundException;
use Illuminate\Support\MultipleItemsFoundException;

class CollectionsTest extends TestCase
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
    }

    public function test_subscriptions_find_by_notification(): void
    {
        $subscriptions = new Subscriptions([
            Subscription::to(ExampleNotification::class),
            Subscription::to(SecondExampleNotification::class),
        ]);

        $this->assertSame(
            ExampleNotification::class,
            $subscriptions->find(ExampleNotification::class)->getNotification()
        );
    }

    public function test_subscriptions_find_missing_throws(): void
    {
        $this->expectException(ItemNotFoundException::class);

        (new Subscriptions([
            Subscription::to(ExampleNotification::class),
        ]))->find('UnknownNotification');
    }

    public function test_subscriptions_find_duplicates_throws(): void
    {
        $this->expectException(MultipleItemsFoundException::class);

        (new Subscriptions([
            Subscription::to(ExampleNotification::class),
            Subscription::to(ExampleNotification::class),
        ]))->find(ExampleNotification::class);
    }

    public function test_subscriptions_names(): void
    {
        $subscriptions = new Subscriptions([
            Subscription::to(ExampleNotification::class),
            Subscription::to(SecondExampleNotification::class),
        ]);

        $this->assertSame([
            ExampleNotification::class,
            SecondExampleNotification::class,
        ], $subscriptions->names());
    }

    public function test_subscriptions_for_filters_by_audience(): void
    {
        $otherUser = User::create([
            'name'     => 'Jane Doe',
            'email'    => 'jane@doe.com',
            'password' => 'secret',
        ]);

        $subscriptions = new Subscriptions([
            Subscription::to(ExampleNotification::class)->for(fn() => User::query()->whereKey($this->user->getKey())),
            Subscription::to(SecondExampleNotification::class),
        ]);

        $this->assertCount(1, $subscriptions->for($this->user));
        $this->assertSame(
            ExampleNotification::class,
            $subscriptions->for($this->user)->first()->getNotification()
        );
        $this->assertCount(0, $subscriptions->for($otherUser));
    }

    public function test_subscriptions_groups_merges_shared_shortcode_keeping_richer(): void
    {
        $group = new Group('Group 1', 'icon');

        $subscriptions = new Subscriptions([
            Subscription::to(ExampleNotification::class)->group('Group 1'),
            Subscription::to(SecondExampleNotification::class)->group($group),
        ]);

        $groups = $subscriptions->groups();

        $this->assertInstanceOf(Groups::class, $groups);
        $this->assertCount(1, $groups);
        $this->assertSame('icon', $groups->first()->getIcon());
    }

    public function test_subscriptions_filter_by_group(): void
    {
        $subscriptions = new Subscriptions([
            Subscription::to(ExampleNotification::class)->group('Group 1'),
            Subscription::to(SecondExampleNotification::class)->group('Group 2'),
        ]);

        $group1 = new Group('Group 1');

        $filtered = $subscriptions->filterByGroup($group1->getShortcode());

        $this->assertCount(1, $filtered);
        $this->assertSame(ExampleNotification::class, $filtered->first()->getNotification());
    }

    public function test_subscriptions_filter_by_group_shortcode(): void
    {
        $group = new Group('Group 1');

        $subscriptions = new Subscriptions([
            Subscription::to(ExampleNotification::class)->group($group),
        ]);

        $this->assertCount(1, $subscriptions->filterByGroup($group->getShortcode()));
    }

    public function test_with_notifiable_appends_channels_and_respects_preferences(): void
    {
        $this->postie->toggleUserPreferences(
            $this->user,
            ExampleNotification::class,
            ['mail' => true, 'telegram' => false]
        );

        $subscription = Subscription::to(ExampleNotification::class)
            ->preview(fn(string $channel) => 'Preview for '.$channel);

        $result = (new Subscriptions([$subscription]))->withNotifiable($this->user);

        $this->assertCount(1, $result);
        $this->assertSame(ExampleNotification::class, $result[0]['notification']);
        $this->assertSame('Weekly Digest', $result[0]['title']);

        $channels = $result[0]['channels'];
        $this->assertCount(2, $channels);

        $mail = collect($channels)->firstWhere('name', 'mail');
        $this->assertTrue($mail['status']);
        $this->assertTrue($mail['available']);
        $this->assertStringContainsString(
            route('postie.preview', [
                'channel'      => 'mail',
                'notification' => ExampleNotification::class,
            ]),
            $mail['previewing']
        );

        $telegram = collect($channels)->firstWhere('name', 'telegram');
        $this->assertFalse($telegram['status']);
        $this->assertTrue($telegram['available']);
        $this->assertStringContainsString(
            route('postie.preview', [
                'channel'      => 'telegram',
                'notification' => ExampleNotification::class,
            ]),
            $telegram['previewing']
        );
    }

    public function test_with_notifiable_marks_unrouted_channels_unavailable(): void
    {
        $subscription = Subscription::to(ExampleNotification::class);

        $result = Subscriptions::make([$subscription])->withNotifiable($this->user);

        $channels = $result[0]['channels'];

        $mail = collect($channels)->firstWhere('name', 'mail');
        $this->assertTrue($mail['available']);

        $absentUser = new User([
            'name'     => 'No Routes',
            'email'    => 'no@routes.com',
        ]);
        $absentUser->email = null;

        $result = Subscriptions::make([$subscription])->withNotifiable($absentUser);

        $channels = $result[0]['channels'];
        $this->assertFalse(collect($channels)->firstWhere('name', 'mail')['available']);
    }

    public function test_with_notifiable_sorts_fallback_groups_to_bottom(): void
    {
        $fallbackSubscription = Subscription::to(ExampleNotification::class);
        $groupedSubscription = Subscription::to(SecondExampleNotification::class)->group('Group 1');

        $result = Subscriptions::make([$fallbackSubscription, $groupedSubscription])
            ->withNotifiable($this->user);

        $this->assertSame([
            SecondExampleNotification::class,
            ExampleNotification::class,
        ], array_column($result, 'notification'));
    }

    public function test_channels_find_and_names(): void
    {
        $channels = new Channels([
            Channel::via('mail'),
            Channel::via('telegram'),
        ]);

        $this->assertSame('mail', $channels->find('mail')->getName());
        $this->assertSame(['mail', 'telegram'], $channels->names());
    }

    public function test_channels_find_missing_throws(): void
    {
        $this->expectException(ItemNotFoundException::class);

        (new Channels([Channel::via('mail')]))->find('unknown');
    }

    public function test_channels_with_notifiable_always_marks_broadcast_available(): void
    {
        $notifiable = new User([
            'name'  => 'No Routes',
            'email' => null,
        ]);

        $channels = (new Channels([
            Channel::via('mail'),
            Channel::via('broadcast'),
        ]))->withNotifiable($notifiable, Subscription::to(ExampleNotification::class));

        $this->assertFalse(collect($channels)->firstWhere('name', 'mail')['available']);
        $this->assertTrue(collect($channels)->firstWhere('name', 'broadcast')['available']);
    }

    public function test_channels_with_notifiable_applies_forced_channel(): void
    {
        $channels = (new Channels([
            Channel::via('mail', default: true, forced: true),
        ]))->withNotifiable($this->user, Subscription::to(ExampleNotification::class));

        $this->assertTrue($channels[0]['status']);
    }

    public function test_channels_get_preferences(): void
    {
        $channels = new Channels([
            Channel::via('mail'),
            Channel::via('telegram', default: false),
        ]);

        $this->assertSame([
            'mail'     => true,
            'telegram' => false,
        ], $channels->getPreferences([]));

        $this->assertSame([
            'mail'     => false,
            'telegram' => true,
        ], $channels->getPreferences(['mail' => false, 'telegram' => true]));
    }

    public function test_groups_filter_by_shortcode(): void
    {
        $group = new Group('Group 1');

        $groups = new Groups([$group]);

        $this->assertCount(1, $groups->filterByShortcode($group->getShortcode()));
        $this->assertCount(0, $groups->filterByShortcode('nope'));
    }

    public function test_groups_has_fallback(): void
    {
        $this->assertFalse((new Groups([new Group('Group 1')]))->hasFallback());
        $this->assertTrue((new Groups([new Group('Group 1'), Group::fallback()]))->hasFallback());
    }

    public function test_groups_reorder_by_weight(): void
    {
        $light = (new Group('Light'))->weight(1);
        $heavy = (new Group('Heavy'))->weight(100);

        $groups = (new Groups([$heavy, $light]))->reorder();

        $this->assertSame('Light', $groups->first()->getTitle());
        $this->assertSame('Heavy', $groups->last()->getTitle());
    }

    public function test_groups_follow_predefined_order(): void
    {
        PostieService::$groups = [
            Group::make('First'),
            Group::make('Second'),
            Group::make('Third'),
        ];

        PostieService::$subscriptions = [
            Subscription::to(ExampleNotification::class)
                ->for(fn() => User::query())
                ->group('Third'),
            Subscription::to(SecondExampleNotification::class)
                ->for(fn() => User::query())
                ->group('Second'),
            Subscription::to(\Codewiser\Postie\Tests\Fixtures\PlainNotification::class)
                ->for(fn() => User::query())
                ->group('First'),
        ];

        $groups = $this->postie
            ->getGroups($this->user)
            ->reorder();

        // Despite subscriptions refer groups in reverse order,
        // predefined groups keep their declaration order.
        $this->assertSame(
            ['First', 'Second', 'Third'],
            $groups->map->getTitle()->values()->all()
        );
    }

    public function test_group_weight_overrides_predefined_order(): void
    {
        PostieService::$groups = [
            Group::make('Second')->weight(10),
            Group::make('First'),
        ];

        PostieService::$subscriptions = [
            Subscription::to(SecondExampleNotification::class)
                ->for(fn() => User::query())
                ->group('Second'),
            Subscription::to(ExampleNotification::class)
                ->for(fn() => User::query())
                ->group('First'),
        ];

        $groups = $this->postie
            ->getGroups($this->user)
            ->reorder();

        // Declared order is Second, First — but weight moves First up.
        $this->assertSame(
            ['First', 'Second'],
            $groups->map->getTitle()->values()->all()
        );
    }
}