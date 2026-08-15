<?php

namespace Codewiser\Postie\Collections;

use Codewiser\Postie\Models\Preference;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Notification;

/**
 * Collection of Preferences.
 *
 * @extends Collection<int, Preference>
 */
class Preferences extends Collection
{
    /**
     * Find first subscription by notification class name.
     *
     * @param  class-string<Notification>  $notification
     */
    public function ofNotification(string $notification): ?Preference
    {
        return $this->first(
            fn(Preference $subscription) => $subscription->notification === $notification
        );
    }
}
