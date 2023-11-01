<?php

namespace Codewiser\Postie\Notifications\Traits;

use Codewiser\Postie\Contracts\Postie;
use function app;

trait Channelization
{
    public function via($notifiable): array
    {
        return app()->call(
            fn(Postie $postie) => $postie->via(get_class($this), $notifiable)
        );
    }
}
