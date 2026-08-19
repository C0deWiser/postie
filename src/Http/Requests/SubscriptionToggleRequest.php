<?php

namespace Codewiser\Postie\Http\Requests;

use Codewiser\Postie\PostieService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Notifications\Notification;
use Illuminate\Validation\Rule;

/**
 * Subscription Toggle Request.
 *
 * @property-read class-string<Notification> $notification Notification class name.
 * @property-read array<string, bool> $channels User preferences.
 * @property-read null|string $variety Variety.
 */
class SubscriptionToggleRequest extends FormRequest
{
    public function rules(PostieService $postie): array
    {
        return [
            'notification' => [
                'required',
                'string',
                Rule::in($postie->getSubscriptions()->names()),
            ],

            'channels' => 'required|array',
            ...$this->getChannelRules($postie),

            'variety' => 'nullable|string'
        ];
    }

    public function getChannelRules(PostieService $postie): array
    {
        $rules = [];

        $subscription = $postie->getSubscriptions()->find($this->notification);

        foreach ($subscription->getChannels()->names() as $name) {
            $rules["channels.$name"] = ['boolean'];
        }

        return $rules;
    }
}
