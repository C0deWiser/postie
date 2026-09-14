<?php

namespace Codewiser\Postie\Http\Requests\Api;

use Codewiser\Postie\PostieService;
use Codewiser\Postie\Subscription;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Subscribe or unsubscribe a user to a single channel of a notification.
 *
 * @property-read class-string $notification Notification class name.
 * @property-read string $channel Channel name.
 */
class SubscriptionChannelRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'notification' => $this->route('notification'),
            'channel'      => $this->route('channel'),
        ]);
    }

    public function rules(PostieService $postie): array
    {
        $rules = [
            'notification' => ['required', 'string'],
            'channel'      => ['required', 'string'],
        ];

        if ($this->subscription($postie)) {
            $rules['channel'][] = Rule::in($this->subscription($postie)->getChannels()->names());
        }

        return $rules;
    }

    /**
     * Get the subscription the route refers to, when it is defined.
     */
    protected function subscription(PostieService $postie): ?Subscription
    {
        $notification = $this->route('notification');

        return in_array($notification, $postie->getSubscriptions()->names(), true)
            ? $postie->getSubscriptions()->find($notification)
            : null;
    }
}