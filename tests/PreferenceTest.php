<?php

namespace Codewiser\Postie\Tests;

use Codewiser\Postie\Collections\Preferences;
use Codewiser\Postie\Events\UserSubscribe;
use Codewiser\Postie\Events\UserUnsubscribe;
use Codewiser\Postie\Http\Resources\PreferenceResource;
use Codewiser\Postie\Models\Preference;
use Codewiser\Postie\PostieService;
use Codewiser\Postie\Tests\Fixtures\ExampleNotification;
use Codewiser\Postie\Tests\Fixtures\SecondExampleNotification;
use Codewiser\Postie\Tests\Models\User;
use Illuminate\Support\Facades\Event;

class PreferenceTest extends TestCase
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

    public function test_preference_table_comes_from_config(): void
    {
        $this->assertSame(config('postie.table'), (new Preference)->getTable());
        $this->assertSame('postie', (new Preference)->getTable());
    }

    public function test_preference_collection_morphs_notifiable(): void
    {
        $preference = new Preference;
        $preference->notifiable()->associate($this->user);
        $preference->notification = ExampleNotification::class;
        $preference->channels = ['mail' => false];
        $preference->save();

        $this->assertTrue($this->user->is($preference->notifiable));
        $this->assertSame(['mail' => false], $preference->channels);
    }

    public function test_for_filters_by_notification(): void
    {
        $this->preference($this->user, ExampleNotification::class, ['mail' => false]);
        $this->preference($this->user, SecondExampleNotification::class, ['telegram' => true]);

        $single = Preference::for($this->user, ExampleNotification::class)->get();
        $this->assertCount(1, $single);
        $this->assertSame(ExampleNotification::class, $single->first()->notification);

        $multiple = Preference::for($this->user, [ExampleNotification::class, SecondExampleNotification::class])->get();
        $this->assertCount(2, $multiple);

        $otherUser = User::create([
            'name'     => 'Jane Doe',
            'email'    => 'jane@doe.com',
            'password' => 'secret',
        ]);
        $this->assertCount(0, Preference::for($otherUser, ExampleNotification::class)->get());
    }

    public function test_collection_of_notification_returns_preferences_collection(): void
    {
        $models = $this->preference($this->user, ExampleNotification::class, ['mail' => false]);

        $collection = Preference::for($this->user)->get();

        $this->assertInstanceOf(Preferences::class, $collection);
        $this->assertSame($models->id, $collection->ofNotification(ExampleNotification::class)->id);
        $this->assertNull($collection->ofNotification(SecondExampleNotification::class));
    }

    public function test_toggle_fires_subscribe_and_unsubscribe_events(): void
    {
        Event::fake([UserSubscribe::class, UserUnsubscribe::class]);

        $this->postie->toggleUserPreferences(
            $this->user,
            ExampleNotification::class,
            ['telegram' => true, 'mail' => false]
        );

        Event::assertDispatched(UserSubscribe::class, fn(UserSubscribe $event) =>
            $event->notification === ExampleNotification::class
            && $event->channel === 'telegram'
        );

        Event::assertDispatched(UserUnsubscribe::class, fn(UserUnsubscribe $event) =>
            $event->notification === ExampleNotification::class
            && $event->channel === 'mail'
        );
    }

    public function test_toggle_does_not_fire_events_for_unchanged_channels(): void
    {
        Event::fake([UserSubscribe::class, UserUnsubscribe::class]);

        // 'mail' defaults to active, so passing true matches the default and is ignored.
        $this->postie->toggleUserPreferences(
            $this->user,
            ExampleNotification::class,
            ['mail' => true]
        );

        Event::assertNotDispatched(UserSubscribe::class);
        Event::assertNotDispatched(UserUnsubscribe::class);
    }

    public function test_preference_resource_shape(): void
    {
        $preference = $this->preference($this->user, ExampleNotification::class, ['mail' => false]);

        $resource = PreferenceResource::make($preference)->resolve();

        $this->assertSame([
            'id'           => $preference->id,
            'channels'     => ['mail' => false],
            'notification' => ExampleNotification::class,
        ], $resource);
    }

    private function preference(User $user, string $notification, array $channels): Preference
    {
        $preference = new Preference;
        $preference->notifiable()->associate($user);
        $preference->notification = $notification;
        $preference->channels = $channels;
        $preference->save();

        return $preference;
    }
}