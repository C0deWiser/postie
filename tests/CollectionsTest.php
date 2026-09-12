<?php

namespace Codewiser\Postie\Tests;

use Codewiser\Postie\Audience;
use Codewiser\Postie\Channel;
use Codewiser\Postie\Collections\Channels;
use Codewiser\Postie\Collections\Groups;
use Codewiser\Postie\Collections\Subscriptions;
use Codewiser\Postie\Group;
use Codewiser\Postie\PostieService;
use Codewiser\Postie\Subscription;
use Codewiser\Postie\Tests\Fixtures\ExampleNotification;
use Codewiser\Postie\Tests\Fixtures\GroupedNotification;
use Codewiser\Postie\Tests\Fixtures\PlainNotification;
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
            Subscription::to(ExampleNotification::class)->for(
                Audience::make('john', 'John')->with(
                    fn() => User::query()->whereKey($this->user->getKey())
                )
            ),
            // A subscription without an audience applies to everyone.
            Subscription::to(SecondExampleNotification::class),
        ]);

        $this->assertCount(2, $subscriptions->for($this->user));
        $this->assertSame(
            ExampleNotification::class,
            $subscriptions->for($this->user)->first()->getNotification()
        );
        $this->assertCount(1, $subscriptions->for($otherUser));
        $this->assertSame(
            SecondExampleNotification::class,
            $subscriptions->for($otherUser)->first()->getNotification()
        );
    }

    public function test_subscription_in_multiple_groups_requires_every_group_audience(): void
    {
        $regular = User::create([
            'name'     => 'John Smith',
            'email'    => 'smith@doe.com',
            'password' => 'secret',
        ]);

        // A user sitting in both group audiences.
        $vip = User::create([
            'name'     => 'Jane Doe',
            'email'    => 'jane@doe.com',
            'password' => 'secret',
        ]);

        $subscriptions = new Subscriptions([
            Subscription::to(ExampleNotification::class)
                ->group(Group::make('Managerial')->for(
                    Audience::make('managers', 'Managers')->with(
                        fn() => User::query()->whereKey([$regular->getKey(), $vip->getKey()])
                    )
                ))
                ->group(Group::make('VIP')->for(
                    Audience::make('vips', 'VIPs')->with(
                        fn() => User::query()->whereKey($vip->getKey())
                    )
                )),
        ]);

        $this->assertCount(0, $subscriptions->for($regular));
        $this->assertCount(1, $subscriptions->for($vip));
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

    public function test_with_notifiable_orders_channels_by_predefined_definitions(): void
    {
        // Predefined channels are declared as: mail, telegram.
        $subscription = Subscription::to(ExampleNotification::class)
            ->via(['telegram', 'mail']);

        $result = (new Subscriptions([$subscription]))->withNotifiable($this->user);

        $this->assertSame(
            ['mail', 'telegram'],
            array_column($result[0]['channels'], 'name')
        );
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

    public function test_with_notifiable_sorts_subscriptions_by_group_weight(): void
    {
        PostieService::$groups = [
            Group::make('Light')->weight(1),
            Group::make('Heavy')->weight(100),
        ];

        $light = Subscription::to(ExampleNotification::class)->group('Light');
        $heavy = Subscription::to(SecondExampleNotification::class)->group('Heavy');

        $result = Subscriptions::make([$heavy, $light])->withNotifiable($this->user);

        $this->assertSame([
            ExampleNotification::class,
            SecondExampleNotification::class,
        ], array_column($result, 'notification'));
    }

    public function test_with_notifiable_uses_biggest_group_weight_for_multi_group_subscription(): void
    {
        PostieService::$groups = [
            Group::make('Light')->weight(1),
            Group::make('Heavy')->weight(100),
        ];

        $light = Subscription::to(ExampleNotification::class)->group('Light');
        $both = Subscription::to(PlainNotification::class)->group('Light')->group('Heavy');
        $heavy = Subscription::to(SecondExampleNotification::class)->group('Heavy');

        $result = Subscriptions::make([$heavy, $both, $light])->withNotifiable($this->user);

        // $both belongs to Light and Heavy: the biggest weight (Heavy) wins.
        // Same-weight subscriptions keep their registration order.
        $this->assertSame([
            ExampleNotification::class,
            SecondExampleNotification::class,
            PlainNotification::class,
        ], array_column($result, 'notification'));
    }

    public function test_with_notifiable_sorts_weightless_groups_by_appearance_order(): void
    {
        PostieService::$groups = [
            Group::make('First'),
            Group::make('Second')->weight(1),
            Group::make('Third'),
        ];

        $first = Subscription::to(ExampleNotification::class)->group('First');
        $second = Subscription::to(SecondExampleNotification::class)->group('Second');
        $third = Subscription::to(PlainNotification::class)->group('Third');

        $result = Subscriptions::make([$third, $second, $first])->withNotifiable($this->user);

        // Weightless groups (First, Third) go first in order of appearance,
        // then the weighted one (Second).
        $this->assertSame([
            ExampleNotification::class,
            PlainNotification::class,
            SecondExampleNotification::class,
        ], array_column($result, 'notification'));
    }

    public function test_with_notifiable_puts_auto_discovered_ungrouped_first(): void
    {
        $discovered = Subscription::to(ExampleNotification::class)->discovered();
        $explicit = Subscription::to(SecondExampleNotification::class);

        $result = Subscriptions::make([$explicit, $discovered])->withNotifiable($this->user);

        $this->assertSame([
            ExampleNotification::class,
            SecondExampleNotification::class,
        ], array_column($result, 'notification'));
    }

    public function test_with_notifiable_keeps_group_order_for_discovered_subscriptions(): void
    {
        PostieService::$groups = [
            Group::make('Light')->weight(1),
        ];

        $discovered = Subscription::to(ExampleNotification::class)
            ->group('Light')
            ->discovered();
        $explicit = Subscription::to(SecondExampleNotification::class)->group('Light');

        $result = Subscriptions::make([$explicit, $discovered])->withNotifiable($this->user);

        // Both are grouped: registration order is preserved, discovered flag does not matter.
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

    public function test_channels_follow_predefined_order(): void
    {
        $defaults = app(PostieService::class)->getChannels();

        $channels = (new Channels([
            Channel::via('telegram'),
            Channel::via('mail'),
        ]))->orderedBy($defaults->all());

        $this->assertSame(
            ['mail', 'telegram'],
            $channels->names()
        );
    }

    public function test_channels_keep_relative_order_when_not_predefined(): void
    {
        $defaults = app(PostieService::class)->getChannels();

        $channels = (new Channels([
            Channel::via('unknown'),
            Channel::via('telegram'),
            Channel::via('mail'),
            Channel::via('another'),
        ]))->orderedBy($defaults->all());

        // Predefined channels go first in their declaration order,
        // unknown channels keep relative order at the end.
        $this->assertSame(
            ['mail', 'telegram', 'unknown', 'another'],
            $channels->names()
        );
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

    public function test_group_defined_by_attribute_only_appears_in_dashboard(): void
    {
        PostieService::$subscriptions = [
            Subscription::to(GroupedNotification::class),
        ];

        // The subscription has no audience, so the group is defined by the attribute only.
        $groups = $this->postie->getGroups($this->user);

        $this->assertSame(['Group'], $groups->map->getTitle()->values()->all());
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
                ->for('everyone')
                ->group('Third'),
            Subscription::to(SecondExampleNotification::class)
                ->for('everyone')
                ->group('Second'),
            Subscription::to(\Codewiser\Postie\Tests\Fixtures\PlainNotification::class)
                ->for('everyone')
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
                ->for('everyone')
                ->group('Second'),
            Subscription::to(ExampleNotification::class)
                ->for('everyone')
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