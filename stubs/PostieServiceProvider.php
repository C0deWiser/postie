<?php

namespace App\Providers;

use Codewiser\Postie\PostieApplicationServiceProvider;

class PostieServiceProvider extends PostieApplicationServiceProvider
{
    /**
     * Return an array of NotificationDefinition
     *
     * @return array
     */
    public function notifications(): array
    {
        return [];
    }
}
