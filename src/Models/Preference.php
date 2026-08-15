<?php

namespace Codewiser\Postie\Models;

use Codewiser\Postie\Collections\Preferences;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

/**
 * User subscriptions preferences.
 *
 * @property integer $id
 * @property null|Carbon $created_at
 * @property null|Carbon $updated_at
 *
 * @property class-string<Notification> $notification Notification class name.
 * @property array<string, bool> $channels Notifiable preferred channels.
 *
 * @property-read Model $notifiable Notifiable.
 */
class Preference extends Model
{
    // Allow Mass Assignment
    protected $guarded = [];

    protected $casts = [
        'channels' => 'array',
    ];

    public function getTable()
    {
        return config('postie.table');
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function newCollection(array $models = []): Preferences
    {
        return new Preferences($models);
    }

    /**
     * Get builder with notifiable subscriptions.
     *
     * @param  null|class-string<Notification>|array<int, class-string<Notification>>  $notification
     *
     * @return Builder<static>
     */
    public static function for(Model $notifiable, string|array $notification = null): Builder
    {
        $builder = static::query()
            ->whereMorphedTo('notifiable', $notifiable);

        if ($notification && is_array($notification)) {
            $builder->whereIn('notification', $notification);
        }

        if (is_string($notification)) {
            $builder->where('notification', $notification);
        }

        return $builder;
    }
}
