# Postie

Postie is a dashboard where users can manage their subscription preferences.

Every `Notification` in application has corresponding audience. It doesn't mean, that everyone from audience will receive a notification, but it is possible. So, Postie lets user decide what channels thay want to be used to deliver notification.

## Installation

Install Postie into your project using the Composer package manager:

    composer require codewiser/postie

After installing Postie, publish its assets using the `postie:install` Artisan command:

    php artisan postie:install

## Configuration

After installing Postie, its service provider will be located at `App\Proviers\PostieServiceProvider`.

First, provide information about every `Notification`, that users may manage:

```php
use Codewiser\Postie\ChannelDefinition;
use Codewiser\Postie\NotificationDefinition;
use Codewiser\Postie\PostieApplicationServiceProvider;

class PostieServiceProvider extends PostieApplicationServiceProvider
{
public function notifications(): array
    {
        return [
            NotificationDefinition::make(NewOrderNotification::class)
                ->audience(fn() => User::query()->where('role', 'sales-manager'))
                ->via(['mail'])
        ];
}
```

Second, replace `Notification::via()` method with `\Codewiser\Postie\Notifications\Traits\Channelization` trait:

```php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Codewiser\Postie\Notifications\Traits\Channelization;

class NewOrderNotification extends Notification implements ShouldQueue
{
    use Queueable, Channelization;

    protected Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject("New order")
            ->line('User makes new order.');
    }

    public function toArray($notifiable)
    {
        return [

        ];
    }
}
```

### Notification Definition

Notification Definition is an object, that helps you describe application notification for Postie to understand.

Initially, it is enough to pass notification class name, query builder with users, who may receive such notification and channels list, supported by notification. 

```php
NotificationDefinition::make(Notification::class)
    ->audience(fn() => User::query())
    ->via(['mail'])
```

Moreover, you may define notification title.

```php
NotificationDefinition::make(Notification::class)
    ->title(__('Daily News Notification'))
    ->audience(fn() => User::query())
    ->via(['mail'])
```

### Channel Definition

When you set up Notification Definition, you may pass channel as a simple string. But there are a way to define more complex channel behaviour.

You may use `\Codewiser\Postie\ChannelDefinition` object to describe channel:

```php
use Codewiser\Postie\ChannelDefinition;

$mail = ChannelDefinition::make('mail')
    ->title(__('via email'));
```

You may define default state of channel. If channel is active, then all users will receive notifications through this channel until they unsubscribe. Vice versa, if channel is passive, all users will not receive notifications through this channel until they subscribe to it.

Default channel state is active.

```php
use Codewiser\Postie\ChannelDefinition;

$mail = ChannelDefinition::make('mail')
    ->title(__('via email'))
    ->passive();
```

If you want to disable user ability to manage channel preferences, you may hide channel form user interface, or just force channel state.

```php
use Codewiser\Postie\ChannelDefinition;

$mail = ChannelDefinition::make('database')
    ->hidden();
```

```php
use Codewiser\Postie\ChannelDefinition;

$mail = ChannelDefinition::make('mail')
    ->title(__('via email'))
    ->forced();
```

## Sending Notifications

Using Postie, you may simply send notification without defining notifiables, as Postie already knows them.

```php
use Codewiser\Postie\Contracts\Postie;

class OrderController extends Controller
{
    public function store(OrderStoreRequest $request, Postie $postie) 
    {
        $order = Order::create($request->validated());
        
        $postie->send(new NewOrderNotification($order));   
    }
}
```

If you need to specify notifiables, you may use a callback:

```php
use Codewiser\Postie\Contracts\Postie;

class OrderController extends Controller
{
    public function store(OrderStoreRequest $request, Postie $postie) 
    {
        $order = Order::create($request->validated());
        
        $postie->send(new NewOrderNotification($order), function($builder) use ($order) {
            if ($order->amount > 10) {
                return $builder->where('level', 'vip');
            } else {
                return $builder->whereNull('level');
            }
        });   
    }
}
```

You even may send notifications that are not registered in Postie. To do that, you must pass notifiables (builder, collection, array or just one user; as a callback or passing a value).

```php
$postie->send(new PasswordRecovery(), $user);
```
