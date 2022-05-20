<?php

namespace Codewiser\Postie\Notifications\Traits;

use Codewiser\Postie\Contracts\Postie;
use function app;

trait Channelization
{
    public function via($notifiable): array
    {
        /* @var Postie $postie */
        $postie = app()->call(function (Postie $postie) {
            return $postie;
        });

        return $postie->via(__CLASS__, $notifiable);
    }
}