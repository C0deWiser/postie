<?php

namespace Codewiser\Postie\Tests;

use Codewiser\Postie\PostieApplicationServiceProvider;
use Codewiser\Postie\PostieServiceProvider;
use Codewiser\Postie\Subscription;
use Codewiser\Postie\Tests\App\PostieServiceProvider as TestPostieServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            PostieServiceProvider::class,
            TestPostieServiceProvider::class,
        ];
    }
}