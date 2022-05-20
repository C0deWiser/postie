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
 * @property-read integer $user_id ID Пользователя
 * @property-read string $notification Название оповещения
 * @property-read array $channels Каналы оповещения
 *
 */
class SubscriptionStoreRequest extends FormRequest
{
    /**
     * @return void
     */
    protected function prepareForValidation()
    {
        // Сразу создаем для пользователя весь массив каналов оповещения
        $postieService = app()->call(function (Postie $postieService) {
            return $postieService;
        });

        $this->merge([
            'channels' => $postieService->findNotificationDefinitionByNotification($this->notification)
                ->getActualChannelsWithStatus($this->channels)
        ]);
    }

    /**
     * Правила валидации
     *
     * @param PostieService $postieService
     * @return array
     */
    public function rules(PostieService $postieService)
    {
        return [
            'user_id' => ['required', 'integer'],
            'notification' => [
                'required',
                'string',
                Rule::in($this->getNotificationNames($postieService)),
                Rule::unique('subscriptions', 'notification')->where(function ($query) {
                    return $query->where('user_id', $this->user_id);
                })],
            'channels' => ['required', 'array'],
        ] + $this->getChannelRules($postieService);
    }

    /**
     * Сообщение об ошибках
     *
     * @return string[]
     */
    public function messages(): array
    {
        return [
            'notification.unique' => 'Такое оповещение у данного пользователя уже существует',
        ];
    }

    /**
     * Массив правил валидации для каналов
     *
     * @param PostieService $postieService
     * @return array
     */
    private function getChannelRules(PostieService $postieService): array
    {
        $channelRules = [];
        foreach ($this->getChannelNames($postieService) as $name) {
            $key = 'channels.' . $name;
            $channelRules[$key] = ['boolean'];
        }
        return $channelRules;
    }

    /**
     * Возможные значение для поля notification
     *
     * @param PostieService $postieService
     * @return array
     */
    private function getNotificationNames(PostieService $postieService): array
    {
        return $postieService
            ->notificationDefinitions()
            ->map(function (NotificationDefinition $notificationDefinition) {
                return $notificationDefinition->getNotification();
            })
            ->toArray();
    }

    /**
     * Возможные значение каналов для оповещения
     * @param PostieService $postieService
     * @return array
     */
    private function getChannelNames(PostieService $postieService): array
    {
        // Определяем возможные каналы для данного оповещения
        $notificationDefinition = $postieService->findNotificationDefinitionByNotification($this->notification);
        return $notificationDefinition->getChannels()->map(function (ChannelDefinition $channelDefinition) {
            return $channelDefinition->getName();
        })->toArray();
    }

}