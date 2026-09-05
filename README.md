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
but it is possible. So, Postie allows the user to decide which channels should
be used to deliver the notification.

![Postie](postie.png)

## Installation

Install Postie into your project using the Composer package manager:

    composer require codewiser/postie

After installing Postie,
publish its assets using the `postie:install` Artisan command:

    php artisan postie:install

Before running migrations, you may want to change the name of the table
that keeps user subscription preferences. Then see `config/postie.php`.

```php
'table' => env('POSTIE_TABLE', 'subscriptions'),
```

After you have configured the table name, run migrations:

    php artisan migrate

## Configuration

After installing Postie, its service provider will be
located at `App\Providers\PostieServiceProvider`.

First, provide information about every `Notification` that users may manage.
Every subscription requires a list of available channels
and a possible audience (as a callable).

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
                ->for(fn() => User::query()->where('role', 'sales-manager'))
        ];
    }
}
```

Second, replace the `Notification::via()` method with the
`\Codewiser\Postie\Notifications\Traits\Channelization` trait.
The `Notification` will use the delivery channels the user preferred.

```php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Codewiser\Postie\Notifications\Traits\Channelization;

class NewOrderNotification extends Notification implements ShouldQueue
{
    use Queueable, Channelization;

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

### Subscription Object

`Subscription` is an object that helps to describe application notifications
for Postie to understand.

Initially, it is enough to pass the notification class name,
a query builder with users who may receive such notification,
and a channels list supported by the notification.

```php
use Codewiser\Postie\Subscription;

Subscription::to(AnyNotification::class)
    ->via('mail')
    ->for(fn() => User::query())
```

Moreover, you may define a notification title and description.

```php
use Codewiser\Postie\Subscription;

Subscription::to(DailyNewsNotification::class)
    ->via('mail')
    ->for(fn() => User::query())
    ->title(__('Daily News Notification'))
    ->description(__('Sends most interesting news digest'))
```

### Channel Object

When you set up a `Subscription`, you may pass a channel as a simple string.
But there is a way to define a more complex channel representation.

You may use the `\Codewiser\Postie\Channel` object to describe a channel
with a custom title, icon, etc.:

```php
use Codewiser\Postie\Channel;
use Codewiser\Postie\Subscription;

$mail = Channel::via('mail')
    ->icon('envelope')
    ->title(__('via email'))
    ->subtitle(__('Sends emails'));

Subscription::to(DailyNewsNotification::class)->via($mail);
```

You may define the default state of a channel. If a channel is active, then
all users will receive notifications through it until they unsubscribe.
Vice versa, if a channel is passive, all users will not receive notifications
via it until they subscribe to it.

Default channel state is active.

```php
use Codewiser\Postie\Channel;

$mail = Channel::via('mail')->passive();
```

If you want to disable the user's ability to manage channel preferences, you
may hide the channel from the user interface, or just force the channel state.

```php
use Codewiser\Postie\Channel;

$mail = Channel::via('database')->hidden();
```

```php
use Codewiser\Postie\Channel;

$mail = Channel::via('mail')->active()->forced();
```

### Grouping Subscriptions

You may group subscriptions to create a side menu for the dashboard.
Subscriptions inherit channels and audience from a group, if defined.

```php
use Codewiser\Postie\Group;
use Codewiser\Postie\Subscription;

// Define group and assign a few subscriptions to it.
Group::make('My group')
    ->icon('broadcast')
    ->via('mail', 'database')
    ->for(fn() => User::query())
    ->add(Subscription::to(DailyNewsNotification::class))
    ->add(Subscription::to(NewOrderNotification::class))

// Assign subscription directly to a group.
Subscription::to(DailyNewsNotification::class)->group('Other group');
```

> Subscription may be assigned to a few groups.

### Previewing Notifications

You may define a notification preview.
So the user can see how the notification will look.

Notification previews may be composed with model factories.

```php
use Codewiser\Postie\Subscription;

Subscription::to(DailyNewsNotification::class)
    ->via('email')
    ->for(fn() => User::query())
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