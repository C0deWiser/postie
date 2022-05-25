<?php

namespace Codewiser\Postie\Models;

use Codewiser\Postie\Collections\SubscriptionCollection;
use Codewiser\Postie\Contracts\Postie;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * User subscriptions preferences.
 *
 * @property integer $id
 * @property-read Model $notifiable Notifiable.
 * @property array $channels Notifiable preferred channels.
 * @property string $notification Notification class name.
 */
class Subscription extends Model
{
    // Allow Mass Assignment
    protected $guarded = [];

    protected $casts = [
        'channels' => 'array',
    ];

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }
    
    public function newCollection(array $models = []): SubscriptionCollection
    {
        return new SubscriptionCollection($models);
    }

    /**
     * Get builder with notifiable subscriptions.
     */
    public static function for(Model $notifiable, $notification = null): Builder
    {
        $builder = static::query()
            ->whereMorphedTo('notifiable', $notifiable);

        if ($notification) {
            if (is_array($notification)) {
                $builder->whereIn('notification', $notification);
            }
            if (is_string($notification)) {
                $builder->where('notification', $notification);
            }
        }

        return $builder;
    }
}
