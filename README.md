# Postie

Postie is a dashboard where users can manage their subscription preferences.

* [Installation](#installation)
* [Configuration](#configuration)
    * [Subscriptions](#subscription-object)
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

First, provide information about every `Notification` that users may manage.
Every subscription requires a list of available channels
and a possible notifiables (as a callable).

```php
use Codewiser\Postie\Subscription;
use Codewiser\Postie\PostieApplicationServiceProvider;

class PostieServiceProvider extends PostieApplicationServiceProvider
{
    public function notifications(): array
    {
        return [
            Subscription::to(NewOrderNotification::class)
                ->via('mail', 'database')
                ->for(fn() => User::query()->where('role', 'sales-manager')),
        ];
    }
}
```

Second, replace the `Notification::via()` method with the
`\Codewiser\Postie\Notifications\Traits\Channelization` trait.
The `Notification` will use the delivery channels the user preferred.

```php
namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Codewiser\Postie\Notifications\Traits\Channelization;

class NewOrderNotification extends Notification
{
    use Channelization;

    public function __construct(public Order $order)
    {
        //
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject("New order")
            ->line('User makes new order.');
    }

    public function toArray($notifiable)
    {
        //
    }
}
```

> This is a minimal setup. All of the following is mostly about making
> the Web panel beautiful.

### Subscription Object

`Subscription` is an object that helps Postie understand the application
notifications.

Initially, it is enough to pass the notification class name,
a query builder with users who may receive such a notification,
and a list of channels supported by the notification.

```php
use Codewiser\Postie\Subscription;

Subscription::to(DailyNewsNotification::class)
    ->via('mail')
    ->for(fn() => User::query());
```

Moreover, you may define a notification title, description, supported channels,
and other properties.

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

If you need translatable title/description, you should pass values directly
to the `Subscription` object.

```php
use Codewiser\Postie\Subscription;

Subscription::to(DailyNewsNotification::class)
    ->for(fn() => User::query())
    ->title(__('Daily News Notification'))
    ->description(__('Sends most interesting news digest'));
```

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
Subscriptions inherit channels and audience from a group, if defined.

```php
use Codewiser\Postie\Group;
use Codewiser\Postie\Subscription;

public function notifications(): array
{
    return [
        // Define group and assign a few subscriptions to it.
        Group::make('My group', icon: 'broadcast')
            ->via('mail', 'database')
            ->for(fn() => User::query())
            ->add(Subscription::to(DailyNewsNotification::class))
            ->add(Subscription::to(NewOrderNotification::class)),

        // Assign subscription directly to a group.
        Subscription::to(AnotherNotification::class)->group('Another group'),
    ];
}
```

> If a Subscription defines its own channels and notifiables — it will not 
> inherit these properties from a group!

Also, you may assign a subscription to a group using the attribute, applied 
to a notification.

```php
namespace App\Notifications;

use Codewiser\Postie\Attributes\Group;
use Illuminate\Notifications\Notification;
use Codewiser\Postie\Notifications\Traits\Channelization;

#[Group('Other group')]
class DailyNewsNotification extends Notification
{
    use Channelization;
}
```

Subscriptions that are not assigned to any group fall into a special
**fallback group** (named "Other" by default, translatable).

> A Subscription may be assigned to a few groups.

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