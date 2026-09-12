<?php

namespace Codewiser\Postie\Tests;

use Codewiser\Postie\Attributes\Channel as ChannelAttribute;
use Codewiser\Postie\Audience;
use Codewiser\Postie\Collections\Subscriptions;
use Codewiser\Postie\Group;
use Codewiser\Postie\PostieService;
use Codewiser\Postie\Subscription;
use Codewiser\Postie\Tests\Fixtures\GroupedNotification;
use Codewiser\Postie\Tests\Fixtures\ArrayChannelNotification;
use Codewiser\Postie\Tests\Fixtures\ArrayGroupedNotification;
use Codewiser\Postie\Tests\Fixtures\ChannelOnlyNotification;
use Codewiser\Postie\Tests\Fixtures\EmptyNotification;
use Codewiser\Postie\Tests\Fixtures\ExampleNotification;
use Codewiser\Postie\Tests\Fixtures\PlainNotification;
use Codewiser\Postie\Tests\Fixtures\PreviewedNotification;
use Codewiser\Postie\Tests\Fixtures\SecondExampleNotification;
use Codewiser\Postie\Tests\Models\User;

class SubscriptionTest extends TestCase
{
    public function test_subscription_reads_attributes_from_notification_class(): void
    {
        $subscription = Subscription::to(ExampleNotification::class);

        $this->assertSame('Weekly Digest', $subscription->getTitle());
        $this->assertSame('Summary of the latest activity in your workspace.', $subscription->getDescription());
        $this->assertSame(['mail', 'telegram'], $subscription->getChannels()->names());
    }

    public function test_channels_inherit_globally_defined_title_and_icon(): void
    {
        $subscription = Subscription::to(ExampleNotification::class);

        $channels = $subscription->getChannels();

        $this->assertSame('E-mail', $channels->find('mail')->getTitle());
        $this->assertSame('bi bi-envelope', $channels->find('mail')->getIcon());
        $this->assertTrue($channels->find('mail')->getDefault());

        $this->assertSame('Telegram', $channels->find('telegram')->getTitle());
        $this->assertSame('bi bi-telegram', $channels->find('telegram')->getIcon());
        // Global definition is passive and the attribute does not override the default.
        $this->assertFalse($channels->find('telegram')->getDefault());
    }

    public function test_channel_attribute_flag_overrides_global_definition(): void
    {
        $channel = (new ChannelAttribute('telegram', default: true))->toChannel();

        // Title and icon are still inherited from the global definition.
        $this->assertSame('Telegram', $channel->getTitle());
        $this->assertSame('bi bi-telegram', $channel->getIcon());
        $this->assertTrue($channel->getDefault());
    }

    public function test_fluent_setters_override_attributes(): void
    {
        $subscription = Subscription::to(ExampleNotification::class)
            ->title('Custom Title')
            ->description('Custom Description')
            ->via(['sms']);

        $this->assertSame('Custom Title', $subscription->getTitle());
        $this->assertSame('Custom Description', $subscription->getDescription());
        $this->assertSame(['sms'], $subscription->getChannels()->names());
    }

    public function test_title_falls_back_to_class_based_name_without_attribute(): void
    {
        $subscription = Subscription::to(PlainNotification::class);

        $this->assertSame('PlainNotification', $subscription->getTitle());
        $this->assertNull($subscription->getDescription());
    }

    public function test_postie_service_resolves_subscription_definition(): void
    {
        $postie = app(PostieService::class);

        $subscription = $postie->getSubscriptions()->find(ExampleNotification::class);

        $this->assertSame(ExampleNotification::class, $subscription->getNotification());
        $this->assertSame('Weekly Digest', $subscription->getTitle());
        $this->assertSame('E-mail', $subscription->getChannels()->find('mail')->getTitle());
    }

    public function test_subscription_reads_array_of_groups_from_attribute(): void
    {
        $subscription = Subscription::to(ArrayGroupedNotification::class);

        $this->assertSame(
            ['Daily', 'Weekly'],
            $subscription->getGroups()->map(fn(Group $group) => $group->getTitle())->toArray()
        );
    }

    public function test_subscription_reads_array_of_channels_from_attribute(): void
    {
        $subscription = Subscription::to(ArrayChannelNotification::class);

        $channels = $subscription->getChannels();

        $this->assertSame(['mail', 'telegram'], $channels->names());

        // Flags are applied to every given channel.
        $this->assertTrue($channels->find('mail')->getDefault());
        $this->assertTrue($channels->find('telegram')->getDefault());
    }

    public function test_group_registers_subscription(): void
    {
        $postie = app(PostieService::class);

        $subscription = $postie->getSubscriptions()->find(PlainNotification::class);

        $this->assertSame(PlainNotification::class, $subscription->getNotification());
        $this->assertSame(['mail'], $subscription->getChannels()->names());
    }

    public function test_group_keeps_subscription_attribute_channels(): void
    {
        $group = Group::make('Test Group')->via(['telegram']);
        $subscription = Subscription::to(PlainNotification::class);

        $group->add($subscription);

        // PlainNotification has its own #[Channel('mail')] attribute,
        // so the group's 'telegram' channel must not override it.
        $this->assertSame(['mail'], $subscription->getChannels()->names());
        $this->assertSame([$subscription], $group->getSubscriptions());
    }

    public function test_group_inherits_channels_to_subscription_without_own(): void
    {
        $group = Group::make('Test Group')->via(['telegram']);

        // A subscription without channel attributes gets channels from the group.
        $subscription = Subscription::to(ExampleNotification::class)
            ->via([]);

        $group->add($subscription);

        $this->assertSame(['telegram'], $subscription->getChannels()->names());
    }

    public function test_groups_with_same_name_merge_keeping_the_richer(): void
    {
        $rich = Group::make('Same Name')
            ->icon('bell')
            ->weight(5)
            ->for(Audience::make('preferences', 'Preferences')
                ->with(fn() => \Codewiser\Postie\Models\Preference::query()));

        $subscriptions = new Subscriptions([
            Subscription::to(PlainNotification::class)->group('Same Name'),
            Subscription::to(ExampleNotification::class)->group($rich),
        ]);

        $groups = $subscriptions->groups();

        $this->assertCount(1, $groups);
        $this->assertSame('bell', $groups->first()->getIcon());
        $this->assertSame(5, $groups->first()->getWeight());
        $this->assertTrue($groups->first()->getAudience()->hasBuilder());
    }

    public function test_groups_with_same_richness_keep_the_first(): void
    {
        $first = Group::make('Same Name')->icon('bell');
        $second = Group::make('Same Name')->icon('pin');

        $subscriptions = new Subscriptions([
            Subscription::to(PlainNotification::class)->group($first),
            Subscription::to(ExampleNotification::class)->group($second),
        ]);

        $groups = $subscriptions->groups();

        $this->assertCount(1, $groups);
        $this->assertSame('bell', $groups->first()->getIcon());
    }

    public function test_app_provider_groups_with_same_name_merge_keeping_the_richer(): void
    {
        $postie = app(PostieService::class);

        $groups = $postie->getSubscriptions()->groups();

        // PlainNotification is in Group::make('Group')->icon('steam'),
        // SecondExampleNotification is in a plain Group('Group').
        // Only the richer (icon 'steam') group must survive the merge.
        $groups = $groups->filter(fn(Group $group) => $group->getIcon() !== 'asterisk');

        $this->assertCount(1, $groups);
        $this->assertSame('Group', $groups->first()->getTitle());
        $this->assertSame('steam', $groups->first()->getIcon());
        $this->assertSame(['telegram'], $groups->first()->getChannels()->names());
    }

    public function test_subscription_reads_group_attribute_from_notification_class(): void
    {
        $subscription = Subscription::to(GroupedNotification::class);

        $this->assertSame(['Group'], $subscription->getGroups()->map(
            fn(Group $group) => $group->getTitle()
        )->toArray());
    }

    public function test_notification_class_name_is_wrapped_into_subscription(): void
    {
        $postie = app(PostieService::class);

        $subscription = $postie->getSubscriptions()->find(GroupedNotification::class);

        $this->assertSame(GroupedNotification::class, $subscription->getNotification());

        // Attributes applied to the notification class are still respected.
        $this->assertSame(['Group'], $subscription->getGroups()->map(
            fn(Group $group) => $group->getTitle()
        )->toArray());
    }

    public function test_notifications_with_postie_attributes_are_discovered_automatically(): void
    {
        config(['postie.notifications_path' => [__DIR__.'/Fixtures']]);

        $postie = app(PostieService::class);

        $names = $postie->getSubscriptions()->names();

        // Explicitly registered notifications are kept.
        $this->assertContains(ExampleNotification::class, $names);
        $this->assertContains(GroupedNotification::class, $names);

        // Notifications applying the Channel attribute are discovered automatically.
        $this->assertContains(ChannelOnlyNotification::class, $names);
    }

    public function test_notifications_are_discovered_across_multiple_paths(): void
    {
        config([
            'postie.notifications_path' => [
                __DIR__.'/Fixtures',
                // A missing directory must be silently skipped.
                __DIR__.'/DoesNotExist',
            ],
        ]);

        $postie = app(PostieService::class);

        $this->assertContains(
            ChannelOnlyNotification::class,
            $postie->getSubscriptions()->names()
        );
    }

    public function test_notifications_path_accepts_comma_separated_string(): void
    {
        config(['postie.notifications_path' => __DIR__.'/Fixtures,/DoesNotExist']);

        $postie = app(PostieService::class);

        $this->assertContains(
            ChannelOnlyNotification::class,
            $postie->getSubscriptions()->names()
        );
    }

    public function test_notification_without_channel_attribute_is_not_discovered(): void
    {
        config(['postie.notifications_path' => [__DIR__.'/Fixtures']]);

        $postie = app(PostieService::class);

        $names = $postie->getSubscriptions()->names();

        // The Preview attribute alone does not make a notification subscribable.
        $this->assertNotContains(PreviewedNotification::class, $names);
    }

    public function test_notifications_without_postie_attributes_are_not_discovered(): void
    {
        config(['postie.notifications_path' => [__DIR__.'/Fixtures']]);

        $postie = app(PostieService::class);

        $this->assertNotContains(
            EmptyNotification::class,
            $postie->getSubscriptions()->names()
        );
    }

    public function test_predefined_groups_are_registered_in_service(): void
    {
        $postie = app(PostieService::class);

        $groups = $postie->getGroups();

        $this->assertCount(1, $groups);
        $this->assertSame('Daily', $groups->first()->getTitle());
        $this->assertSame('broadcast', $groups->first()->getIcon());
        $this->assertSame(3, $groups->first()->getWeight());
    }

    public function test_subscription_references_predefined_group_by_name(): void
    {
        $subscription = Subscription::to(ExampleNotification::class)
            ->via([])
            ->group('Daily');

        $group = $subscription->getGroups()->first();

        $this->assertSame('Daily', $group->getTitle());
        $this->assertSame('broadcast', $group->getIcon());
        $this->assertSame(3, $group->getWeight());

        // Subscriptions inherit channels from a predefined group.
        $this->assertSame(['mail'], $subscription->getChannels()->names());
    }

    public function test_subscription_keeps_own_channels_when_referencing_predefined_group(): void
    {
        $subscription = Subscription::to(ExampleNotification::class)
            ->group('Daily');

        // ExampleNotification defines its own channels via the Channel attribute.
        $this->assertSame(['mail', 'telegram'], $subscription->getChannels()->names());
    }

    public function test_subscription_inherits_audience_from_predefined_group(): void
    {
        $subscription = Subscription::to(ExampleNotification::class)
            ->via([])
            ->group('Daily');

        // Subscription has no own audience, so it inherits the group audience.
        $this->assertInstanceOf(
            \Illuminate\Contracts\Database\Eloquent\Builder::class,
            $subscription->getAudience()->getBuilder()
        );
    }

    public function test_subscription_keeps_own_audience_when_referencing_predefined_group(): void
    {
        $own = Audience::make('none', 'None')
            ->with(fn() => \Codewiser\Postie\Tests\Models\User::query()->whereKey(-1));

        $subscription = Subscription::to(ExampleNotification::class)
            ->for($own)
            ->group('Daily');

        // Subscription has its own audience, so the group audience must not be inherited.
        $this->assertSame($own->getCallback(), $subscription->getAudience()->getCallback());
    }

    public function test_subscription_to_array_lists_every_group_audience(): void
    {
        $subscription = Subscription::to(ExampleNotification::class)
            ->group(Group::make('Managerial')->for(
                Audience::make('managers', 'Managers')->with(fn() => User::query())
            ))
            ->group(Group::make('VIP')->for(
                Audience::make('vips', 'VIPs')->with(fn() => User::query())
            ));

        $this->assertSame(
            ['Managers', 'VIPs'],
            $subscription->toArray()['audiences']
        );
    }

    public function test_subscription_own_audience_ignores_group_audience(): void
    {
        // A user sitting in the own audience only.
        $ownUser = User::create([
            'name'     => 'John Smith',
            'email'    => 'smith@doe.com',
            'password' => 'secret',
        ]);

        // A user sitting in the group audience only.
        $otherUser = User::create([
            'name'     => 'Jane Doe',
            'email'    => 'jane@doe.com',
            'password' => 'secret',
        ]);

        $subscription = Subscription::to(ExampleNotification::class)
            ->for(Audience::make('own', 'Own')->with(fn() => User::query()->whereKey($ownUser->getKey())))
            ->group(Group::make('Other')->for(
                Audience::make('others', 'Others')->with(fn() => User::query()->whereKey($otherUser->getKey()))
            ));

        $subscriptions = new Subscriptions([$subscription]);

        // In own audience, despite not belonging to the group audience.
        $this->assertCount(1, $subscriptions->for($ownUser));

        // In group audience only, not in own audience.
        $this->assertCount(0, $subscriptions->for($otherUser));

        // Own audience is displayed, group audiences are hidden.
        $this->assertSame(['Own'], $subscription->getAudienceTitles());
    }
}