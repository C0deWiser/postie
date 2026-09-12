<?php

namespace Codewiser\Postie\Tests;

use Codewiser\Postie\Audience;
use Codewiser\Postie\Channel;
use Codewiser\Postie\Group;
use Codewiser\Postie\PostieService;
use Codewiser\Postie\Subscription;
use Codewiser\Postie\Tests\Fixtures\ExampleNotification;
use Codewiser\Postie\Tests\Fixtures\GroupedNotification;
use Codewiser\Postie\Tests\Models\User;

class UnitTest extends TestCase
{
    private PostieService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(PostieService::class);
    }

    public function test_channel_fluent_setters_are_immutable(): void
    {
        $original = Channel::via('mail');

        $clone = $original
            ->title('E-mail')
            ->subtitle('Send to email')
            ->default(false)
            ->forced()
            ->hidden()
            ->icon('envelope')
            ->router('/settings');

        // Original definition is untouched.
        $this->assertNull($original->getSubtitle());
        $this->assertFalse($original->getForced());
        $this->assertFalse($original->getHidden());
        $this->assertSame('bi bi-envelope', $original->getIcon());
        $this->assertNull($original->getRouter());

        // Clone carries the new settings.
        $this->assertSame('E-mail', $clone->getTitle());
        $this->assertSame('Send to email', $clone->getSubtitle());
        $this->assertFalse($clone->getDefault());
        $this->assertTrue($clone->getForced());
        $this->assertTrue($clone->getHidden());
        $this->assertSame('bi bi-envelope', $clone->getIcon());
        $this->assertSame('/settings', $clone->getRouter());
    }

    public function test_channel_fluent_flags_set_defaults(): void
    {
        $this->assertTrue(Channel::via('mail')->active()->getDefault());
        $this->assertFalse(Channel::via('mail')->passive()->getDefault());
        $this->assertTrue(Channel::via('mail')->forced()->getForced());
        $this->assertFalse(Channel::via('mail')->forced(false)->getForced());
        $this->assertTrue(Channel::via('mail')->hidden()->getHidden());
        $this->assertFalse(Channel::via('mail')->hidden(false)->getHidden());
    }

    public function test_channel_icon_flags_from_names(): void
    {
        $this->assertSame('bi bi-envelope', Channel::via('mail')->getIcon());
        $this->assertSame('bi bi-telegram', Channel::via('telegram')->getIcon());
        $this->assertSame('bi bi-chat', Channel::via('sms')->getIcon());
        $this->assertSame('bi bi-window', Channel::via('broadcast')->getIcon());
        $this->assertSame('bi bi-record-circle', Channel::via('custom')->getIcon());
    }

    public function test_channel_title_from_name(): void
    {
        $this->assertSame('Mail', Channel::via('mail')->getTitle());
        $this->assertSame('Telegram', Channel::via('telegram')->getTitle());
        $this->assertSame('Sms', Channel::via('sms')->getTitle());
    }

    public function test_channel_to_array_shape(): void
    {
        $channel = Channel::via('mail')->title('E-mail')->subtitle('desc');

        $this->assertSame([
            'name'     => 'mail',
            'title'    => 'E-mail',
            'subtitle' => 'desc',
            'default'  => true,
            'forced'   => false,
            'hidden'   => false,
            'icon'     => 'bi bi-envelope',
            'router'   => null,
        ], $channel->toArray());
    }

    public function test_channel_get_preferences_respects_forced(): void
    {
        $channel = Channel::via('mail', default: false, forced: true);

        // Forced channel ignores user preferences.
        $this->assertFalse($channel->getPreferences(true));
        $this->assertFalse($channel->getPreferences(false));
    }

    public function test_channel_get_preferences_uses_user_prefs_when_present(): void
    {
        $channel = Channel::via('mail');

        $this->assertFalse($channel->getPreferences(false));
        $this->assertTrue($channel->getPreferences(true));
    }

    public function test_channel_get_preferences_falls_back_to_default(): void
    {
        $channel = Channel::via('mail', default: false);

        $this->assertFalse($channel->getPreferences(null));
        $this->assertFalse($channel->getPreferences());
    }

    public function test_group_fallback_is_appended_to_subscription_without_groups(): void
    {
        $subscription = Subscription::to(ExampleNotification::class);

        $group = $subscription->getGroups()->sole();

        $this->assertTrue($group->isFallback());
        $this->assertSame([
            'shortcode' => $group->getShortcode(),
            'name'      => $group->getTitle(),
            'icon'      => $group->getIcon(),
            'fallback'  => true,
            'weight'    => PHP_INT_MAX,
        ], $group->toArray());
    }

    public function test_group_make_sets_title_and_default_icon(): void
    {
        $group = Group::make('Subscribers');

        $this->assertSame('Subscribers', $group->getTitle());
        $this->assertSame('asterisk', $group->getIcon());
        $this->assertFalse($group->isFallback());
        $this->assertSame(0, $group->getWeight());
    }

    public function test_group_fluent_setters(): void
    {
        $group = Group::make('First')->icon('bell')->weight(10);

        $this->assertSame('bell', $group->getIcon());
        $this->assertSame(10, $group->getWeight());
    }

    public function test_group_shortcode_is_derived_from_title(): void
    {
        $group = new Group('Unique Group Name');

        $this->assertSame(
            substr(md5('Unique Group Name'), 0, 4),
            $group->getShortcode()
        );
    }

    public function test_group_rank_counts_explicit_attributes(): void
    {
        $plain = new Group('Plain');
        $rich = Group::make('Rich')->icon('bell')->weight(5);

        $this->assertSame(0, $plain->getRank());
        $this->assertSame(2, $rich->getRank());
    }

    public function test_group_add_inherits_audience_to_subscription_without_one(): void
    {
        $group = Group::make('Group')->for('everyone');
        $subscription = Subscription::to(ExampleNotification::class);

        $group->add($subscription);

        $this->assertTrue($subscription->getAudience()->hasBuilder());
    }

    public function test_group_add_accepts_notification_class_name(): void
    {
        $group = Group::make('Group')->via('mail')->for('everyone');

        $group->add(GroupedNotification::class);

        $subscription = $group->getSubscriptions()[0];

        $this->assertSame(GroupedNotification::class, $subscription->getNotification());
        $this->assertTrue($subscription->getAudience()->hasBuilder());
        $this->assertSame(['mail'], $subscription->getChannels()->names());
    }

    public function test_group_add_keeps_subscription_audience_if_present(): void
    {
        $group = Group::make('Group')->for('everyone');
        $subscription = Subscription::to(ExampleNotification::class)
            ->for(Audience::make('no-email', 'No E-mail')
                ->with(fn() => User::query()->whereNull('email')));

        $group->add($subscription);

        $this->assertSame(
            'select * from "users" where "email" is null',
            $subscription->getAudience()->getBuilder()->toSql()
        );
    }

    public function test_subscription_to_array_shape(): void
    {
        $subscription = Subscription::to(ExampleNotification::class);

        $array = $subscription->toArray();

        $this->assertSame(ExampleNotification::class, $array['notification']);
        $this->assertSame('Weekly Digest', $array['title']);
        $this->assertSame(
            'Summary of the latest activity in your workspace.',
            $array['description']
        );
        $this->assertInstanceOf(\Codewiser\Postie\Collections\Groups::class, $array['groups']);
        $this->assertTrue($array['groups']->first()->isFallback());
        $this->assertSame(['mail', 'telegram'], array_column($array['channels'], 'name'));
    }

    public function test_subscription_via_variadic(): void
    {
        $subscription = Subscription::to(ExampleNotification::class)
            ->via('mail', 'telegram');

        $this->assertSame(['mail', 'telegram'], $subscription->getChannels()->names());
    }

    public function test_subscription_groups_returns_assigned_groups(): void
    {
        $subscription = Subscription::to(ExampleNotification::class)->group('Group 1');

        $this->assertSame(['Group 1'], $subscription->getGroups()->map(
            fn(Group $group) => $group->getTitle()
        )->toArray());
        $this->assertFalse($subscription->getGroups()->first()->isFallback());
    }

    public function test_postie_service_script_variables(): void
    {
        config([
            'postie.dashboard.badges.audience' => false,
            'postie.dashboard.badges.groups'   => false,
        ]);

        $this->assertSame([
            'path'              => config('postie.path'),
            'showAudienceBadge' => false,
            'showGroupsBadge'   => false,
        ], app(PostieService::class)->scriptVariables());
    }
}