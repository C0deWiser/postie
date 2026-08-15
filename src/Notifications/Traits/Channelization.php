<?php

namespace Codewiser\Postie\Notifications\Traits;

use Codewiser\Postie\PostieService;
use function app;

/**
 * Manageable Delivery Channels.
 */
trait Channelization
{
    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return app()->call(
            fn(PostieService $postie) => $postie->via(get_class($this), $notifiable)
        );
    }
}
