<?php

namespace Codewiser\Postie\Http\Requests;

use Codewiser\Postie\ChannelDefinition;
use Codewiser\Postie\Contracts\Postie;
use Codewiser\Postie\NotificationDefinition;
use Codewiser\Postie\PostieService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Subscription Store Request.
 *
 * @property-read string $notification Notification class name.
 * @property-read array $channels User preferences.
 */
class SubscriptionToggleRequest extends FormRequest
{
    public function rules(Postie $postie)
    {
        return [
                'notification' => [
                    'required',
                    'string',
                    Rule::in($postie->getNotifications()->classNames()),
                ],
            ] + $this->getChannelRules($postie);
    }

    public function getChannelRules(Postie $postie): array
    {
        $channelRules = [
            'channels' => ['required', 'array'],
        ];

        $definition = $postie->getNotifications()->find($this->notification);

        foreach ($definition->getChannelNames() as $channelName) {
            $key = 'channels.' . $channelName;
            $channelRules[$key] = ['boolean'];
        }

        return $channelRules;
    }
}
