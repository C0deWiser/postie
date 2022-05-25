<?php

namespace Codewiser\Postie\Http\Resources;

use Codewiser\Postie\Models\Subscription;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Subscription
 */
class SubscriptionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'channels' => $this->channels,
            'notification' => $this->notification,
        ];
    }
}
