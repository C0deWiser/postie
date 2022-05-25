<?php

namespace Codewiser\Postie\Http\Requests;

use Codewiser\Postie\ChannelDefinition;
use Codewiser\Postie\Contracts\Postie;
use Codewiser\Postie\NotificationDefinition;
use Codewiser\Postie\PostieService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Subscription Store Request
 *
 * @property-read string $notification Название оповещения
 * @property-read array $channels Каналы оповещения
 *
 */
class SubscriptionToggleRequest extends FormRequest
{
    /**
     * Правила валидации
     *
     * @param PostieService $postieService
     * @return array
     */
    public function rules(Postie $postie)
    {
        return [
                'notification' => [
                    'required',
                    'string',
                    Rule::in($postie->getNotificationNames()),
                ],
            ] + $this->getChannelRules($postie);
    }

    /**
     * Массив правил валидации для каналов
     *
     * @return array
     */
    public function getChannelRules(Postie $postie): array
    {
        $channelRules = [
            'channels' => ['required', 'array'],
        ];

        $notificationDefinition = $postie->findNotificationDefinitionByNotification($this->notification);

        foreach ($notificationDefinition->getChannelNames() as $channelName) {
            $key = 'channels.' . $channelName;
            $channelRules[$key] = ['boolean'];
        }
        return $channelRules;
    }
}