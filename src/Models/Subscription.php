<?php

namespace Codewiser\Postie\Models;

use App\Models\User;
use Codewiser\Postie\ChannelDefinition;
use Codewiser\Postie\Contracts\Postie;
use Codewiser\Postie\Models\Contracts\Subscriptionable;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Правило оповещения пользователя о событии на сайте
 *
 * @property integer $id ID
 * @property array $channels Массив каналов
 * @property string $notification Оповещение
 *
 * @property-read array $resolved_channels Разрезолвенный массив каналов исходя из определений
 */
class Subscription extends Model
{
    // Разрешаем полный Mass Assignment
    protected $guarded = [];

//    protected $fillable = [
//        'user_id',
//        'notification',
//        'channels',
//    ];

    protected $casts = [
        'channels' => 'array',
    ];

    /**
     * Геттер актуального массива использования каналов исходя из определения оповещений
     * @return array
     */
    public function getResolvedChannelsAttribute()
    {
        /** @var Postie $postie */
        $postie = app()->call(function (Postie $postie) {
            return $postie;
        });
        $notificationDefinition = $postie->findNotificationDefinitionByNotification($this->notification);

        $result = [];
        foreach ($notificationDefinition->getChannels() as $channel) {
            $result[$channel->getName()] =
                $channel->getForced()
                    ? $channel->getDefault()
                    : (
                        count($this->channels) && array_key_exists($channel->getName(), $this->channels)
                            ? $this->channels[$channel->getName()]
                            : $channel->getDefault()
                        );
        }
        return $result;
    }
}