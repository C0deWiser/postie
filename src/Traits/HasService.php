<?php

namespace Codewiser\Postie\Traits;

use Codewiser\Postie\PostieService;

trait HasService
{
    /**
     * @var callable|PostieService
     */
    protected static $service;

    /**
     * @param  callable(): PostieService  $service
     */
    public static function useService(callable $service): void
    {
        static::$service = $service;
    }

    protected function getService(): PostieService
    {
        if (is_callable(static::$service)) {
            static::$service = call_user_func(static::$service);
        }

        return static::$service;
    }
}