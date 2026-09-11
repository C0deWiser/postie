<?php

namespace Codewiser\Postie\Tests;

use Codewiser\Postie\Attributes\Channel as ChannelAttribute;
use Codewiser\Postie\Collections\Subscriptions;
use Codewiser\Postie\Group;
use Codewiser\Postie\PostieService;
use Codewiser\Postie\Subscription;
use Codewiser\Postie\Tests\Fixtures\GroupedNotification;
use Codewiser\Postie\Tests\Fixtures\PlainNotification;
use Codewiser\Postie\Tests\Fixtures\ExampleNotification;

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
            ->for(fn() => \Codewiser\Postie\Models\Preference::query());

        $subscriptions = new Subscriptions([
            Subscription::to(PlainNotification::class)->group('Same Name'),
            Subscription::to(ExampleNotification::class)->group($rich),
        ]);

        $groups = $subscriptions->groups();

        $this->assertCount(1, $groups);
        $this->assertSame('bell', $groups->first()->getIcon());
        $this->assertSame(5, $groups->first()->getWeight());
        $this->assertTrue($groups->first()->hasAudience());
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
            $subscription->getAudience()
        );
    }

    public function test_subscription_keeps_own_audience_when_referencing_predefined_group(): void
    {
        $own = fn() => \Codewiser\Postie\Tests\Models\User::query()->whereKey(-1);

        $subscription = Subscription::to(ExampleNotification::class)
            ->for($own)
            ->group('Daily');

        // Subscription has its own audience, so the group audience must not be inherited.
        $this->assertSame($own, $subscription->getAudienceCallback());
    }
}