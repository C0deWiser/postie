<?php

namespace Codewiser\Postie\Http\Resources;

use Codewiser\Postie\Models\Preference;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Preference
 */
class PreferenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'channels'     => $this->channels,
            'notification' => $this->notification,
        ];
    }
}
