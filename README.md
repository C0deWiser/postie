# Postie

Postie is a dashboard where users can manage their subscription preferences.

* [Installation](#installation)
* [Configuration](#configuration)
    * [Channels](#channel-object)
    * [Groups](#grouping-subscriptions)
    * [Preview](#previewing-notifications)

Every `Notification` in the application has a corresponding audience.
It doesn't mean that everyone from the audience will receive a notification,
but it is possible. Postie **allows the user to decide** which channels
should be used to deliver the notification.

![Postie](postie.png)

## Installation

Install Postie into your project using the Composer package manager:

    composer require codewiser/postie

After installing Postie,
publish its resources (provider, assets, migrations) using the
`postie:install` Artisan command:

    php artisan postie:install

Before running migrations, you may want to change the name of the table
that keeps user subscription preferences. The configuration file is published
separately with Laravel's `vendor:publish` command:

    php artisan vendor:publish --tag=postie-config

Then see `config/postie.php`:

```php
'table' => env('POSTIE_TABLE', 'postie'),
```

After you have configured the table name, run migrations:

    php artisan migrate

To refresh Postie assets after update, use the `postie:publish` Artisan command:

    php artisan postie:publish

Postie UI strings and the default group name are translatable.
Publish Postie translations to override them:

    php artisan vendor:publish --tag=postie-translations

## Configuration

After installing Postie, its service provider will be
located at `App\Providers\PostieServiceProvider`.

**First**, provide information about every `Notification` that users may manage.
Every subscription requires a list of available channels and an audience.
Audience — is a User Builder. If current user is not in notification 
audience, he or she can not manage subscription to the notification.
If an audience is not set, it means that every user may manage such
notification.

Postie can discover notifications automatically:
any notification class in the `app/Notifications` directory that applies the
`Channel(s)` or `Group(s)` attribute is registered on its own.

To use a different directory, publish the `postie` config and set the
`notifications_path` option to an array of directories.

**Second**, replace the `Notification::via()` method with the
`\Codewiser\Postie\Notifications\Traits\Channelization` trait.
The `Notification` will use the delivery channels the user preferred.

```php
namespace App\Notifications;

use Codewiser\Postie\Attributes\Channel;
use Codewiser\Postie\Attributes\Description;
use Codewiser\Postie\Attributes\Subject;
use Illuminate\Notifications\Notification;
use Codewiser\Postie\Notifications\Traits\Channelization;

#[Subject('Daily News Notification')]
#[Description('Sends most interesting news digest')]
#[Channel('mail')]
class DailyNewsNotification extends Notification
{
    use Channelization;
}
```

> Class-level attributes are not inherited from a parent class: apply them 
> to each Notification that must use them.

If you don't like php attributes, or if you need translatable 
title/description — define subscriptions in `PostieServiceProvider` explicitly. 
You may combine both approaches: notification attributes are prior.

```php
use Codewiser\Postie\Subscription;
use Codewiser\Postie\PostieApplicationServiceProvider;

class PostieServiceProvider extends PostieApplicationServiceProvider
{
    public function notifications(): array
    {
        return [
            Subscription::to(DailyNewsNotification::class)
                ->via('mail')
                ->title(__('Daily News Notification'))
                ->description(__('Sends most interesting news digest')),
        ];
    }
}
```

> This is a minimal setup. All of the following is mostly about making
> the Web panel beautiful.

### Channel Object

When you set up a `Subscription`, you may pass a channel as a simple string.
But there is a way to define a more complex channel representation.

You may use the `\Codewiser\Postie\Channel` object to describe a channel
with a custom title, icon, etc.

The easiest approach is to describe all available channels in the Service
Provider. Here you set up channel default properties. Later you may override
channel properties per notification.

```php
use Codewiser\Postie\Channel;
use Codewiser\Postie\PostieApplicationServiceProvider;

class PostieServiceProvider extends PostieApplicationServiceProvider
{
    public function channels(): array
    {
        return [
            Channel::via('mail')
                ->icon('envelope')
                ->title(__('via email'))
                ->subtitle(__('Sends emails'))
                ->passive(),
        ];
    }
}
```

When a `Subscription` gets a channel by its name, the channel
definition inherits these default properties (title, icon, etc.)
unless they are overridden by the channel attribute or fluent method.
If a channel hasn't been described in the Service Provider,
it gets built-in defaults (title and icon derived from its name).

You may define the default state of a channel. If a channel is active, then
all users will receive notifications through it until they unsubscribe.
Vice versa, if a channel is passive, users will not receive notifications
through it until they subscribe.

Default channel state is active.

```php
namespace App\Notifications;

use Codewiser\Postie\Attributes\Channel;
use Illuminate\Notifications\Notification;
use Codewiser\Postie\Notifications\Traits\Channelization;

#[Channel('mail', default: false)]
class DailyNewsNotification extends Notification
{
    use Channelization;
}
```

You may apply the same properties to a few channels at once using the
`Channels` attribute:

```php
use Codewiser\Postie\Attributes\Channels;

#[Channels(['mail', 'telegram'], default: false)]
class DailyNewsNotification extends Notification
{
    use Channelization;
}
```

If you want to disable the user's ability to manage channel preferences, you
may hide the channel from the user interface, or just force the channel state.

```php
namespace App\Notifications;

use Codewiser\Postie\Attributes\Channel;
use Illuminate\Notifications\Notification;
use Codewiser\Postie\Notifications\Traits\Channelization;

#[Channel('mail', forced: true)]
#[Channel('database', hidden: true)]
class DailyNewsNotification extends Notification
{
    use Channelization;
}
```

### Grouping Subscriptions

You may group subscriptions to create a side menu for the dashboard.

The easiest approach is to describe all groups in the Service Provider.
Here you set up group default properties (icon, weight, channels, audience).
Later a subscription may reference a group by its name and inherit these
properties.

```php
use Codewiser\Postie\Audience;
use Codewiser\Postie\Group;

public function groups(): array
{
    return [
        Group::make('My group', icon: 'broadcast')
            ->via('mail', 'database'),
    ];
}
```

In `notifications()` you may reference a predefined group by its name:

```php
use Codewiser\Postie\Subscription;

public function notifications(): array
{
    return [
        Subscription::to(DailyNewsNotification::class)->group('My group'),
        Subscription::to(NewOrderNotification::class)->group('My group'),
    ];
}
```

Or define a group right inside `notifications()`:

```php
public function notifications(): array
{
    return [
        // Define group and assign a few subscriptions to it.
        Group::make('My group', icon: 'broadcast')
            ->via('mail', 'database')
            ->add(DailyNewsNotification::class)
            ->add(Subscription::to(NewOrderNotification::class)),

        // Assign subscription directly to a group.
        Subscription::to(AnotherNotification::class)->group('Another group'),
    ];
}
```

> If a Subscription defines its own channels and audience — it will not 
> inherit these properties from a group!

Also, you may assign a subscription to a group using the attribute, applied 
to a notification.

```php
namespace App\Notifications;

use Codewiser\Postie\Attributes\Group;
use Illuminate\Notifications\Notification;
use Codewiser\Postie\Notifications\Traits\Channelization;

#[Group('Another group')]
class DailyNewsNotification extends Notification
{
    use Channelization;
}
```

To assign a notification to a few groups at once, use the `Groups` attribute:

```php
use Codewiser\Postie\Attributes\Groups;

#[Groups(['Daily', 'Weekly'])]
class DailyNewsNotification extends Notification
{
    use Channelization;
}
```

Subscriptions that are not assigned to any group fall into a special
**fallback group** (named "Other" by default, translatable).

> A Subscription may be assigned to a few groups.

### Audience

Predefine possible notifiables (audience) in the service provider.
Each audience has a name (a `BackedEnum` is supported), a title
and a callback, returning a Builder of possible notifiables.

```php
use Codewiser\Postie\Audience;

public function audiences(): array
{
    return [
        Audience::make('managers', 'Managers')->for(
            fn() => User::query()->where('role', 'manager')
        ),
    ];
}
```

A notification may be scoped to an audience using the `Audience` attribute.
The subscription adopts the builder of the predefined audience, referenced
by its name.

```php
namespace App\Notifications;

use Codewiser\Postie\Attributes\Audience;
use Illuminate\Notifications\Notification;
use Codewiser\Postie\Notifications\Traits\Channelization;

#[Audience('managers')]
class ManagersDigestNotification extends Notification
{
    use Channelization;
}
```

A `Subscription` or a `Group` may also reference a predefined audience by its
name, or accept an `Audience` object, in the `for()` method:

```php
use Codewiser\Postie\Audience;

public function notifications(): array
{
    return [
        // Reference a predefined audience by name.
        Subscription::to(DigestNotification::class)->for('managers'),

        // Or use an audience object.
        Subscription::to(ReportNotification::class)->for(
            Audience::make('managers', 'Managers')->for(
                fn() => User::query()->where('role', 'manager')
            )
        ),
    ];
}
```

When a subscription defines its own audience, that audience wins: group 
audiences are ignored. Otherwise, when the audience comes from groups, the 
notifiable must belong to every group audience.

### Previewing Notifications

You may define a notification preview,
so the user can see how the notification will look.

Notification previews may be composed with model factories.

```php
use Codewiser\Postie\Subscription;

Subscription::to(DailyNewsNotification::class)
    ->preview(function(string $channel, object $notifiable) {

        $news = NewsItem::factory()->count(3)->make();

        $notification = new DailyNewsNotification($news);

        return match ($channel) {
            'mail'      => $notification->toMail($notifiable),
            'telegram'  => $notification->toTelegram($notifiable),
            'database',
            'broadcast' => $notification->toArray($notifiable),
        };
    });
```

Alternatively, you may define the preview inside the Notification class.
Mark a static method with the `Preview` attribute — Postie will use it
to compose the preview.

> Such method should return a callable.

```php
namespace App\Notifications;

use Codewiser\Postie\Attributes\Preview;
use Illuminate\Notifications\Notification;
use Codewiser\Postie\Notifications\Traits\Channelization;

class DailyNewsNotification extends Notification
{
    use Channelization;

    #[Preview]
    public static function preview(): callable
    {
        return function (string $channel, object $notifiable) {

            $news = NewsItem::factory()->count(3)->make();

            $notification = new DailyNewsNotification($news);

            return match ($channel) {
                'mail'      => $notification->toMail($notifiable),
                'database',
                'broadcast' => $notification->toArray($notifiable),
            };
        };
    }
}
```

> The `Preview` attribute will be inherited form a parent classes of 
> notification.

Previews defined via the subscription `preview()` method take precedence
over the `Preview` attribute.