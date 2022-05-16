<?php

namespace Codewiser\Postie;

use Illuminate\Support\ServiceProvider;

abstract class PostieApplicationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        PostieService::$notifications = $this->notifications();
    }

    /**
     * Return an array of NotificationDefinition
     *
     * @return array
     */
    abstract public function notifications(): array;
}
