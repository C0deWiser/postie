<?php

namespace Codewiser\Postie\Tests;

use Codewiser\Postie\Channel;
use Codewiser\Postie\PostieService;
use Codewiser\Postie\Subscription;
use Codewiser\Postie\Tests\Fixtures\ExampleNotification;

class ChannelTest extends TestCase
{
    private PostieService $service;

    protected function setUp(): void
    {
        parent::setUp();

        PostieService::$channels = [];

        $this->service = $this->app->make(PostieService::class);
    }

    public function test_definitions_are_resolved_lazily_and_cached(): void
    {
        $resolved = 0;

        PostieService::$channels = function () use (&$resolved) {
            $resolved++;

            return [
                Channel::via('mail')->title('E-mail'),
            ];
        };

        // Closure is not evaluated until channel is requested.
        $this->assertSame(0, $resolved);

        $this->assertSame('E-mail', $this->service->getChannels()->find('mail')->getTitle());
        $this->assertSame(1, $resolved);

        // Subsequent requests use cached definitions, closure is not called again.
        $this->service->getChannels()->find('mail');
        $this->assertSame(1, $resolved);
    }

    public function test_definitions_are_stored_in_postie_service(): void
    {
        PostieService::$channels = fn() => [
            Channel::via('mail')->title('E-mail'),
        ];

        $this->assertSame('E-mail', $this->service->getChannels()->find('mail')->getTitle());
        $this->assertSame(['mail' => 'mail'], $this->service->getChannels()->names());
    }

    public function test_undefined_channel_falls_back_to_auto_definition(): void
    {
        $subscription = Subscription::to(ExampleNotification::class)->via(['unknown']);

        $channel = $subscription->getChannels()->find('unknown');

        $this->assertSame('Unknown', $channel->getTitle());
        $this->assertTrue($channel->getDefault());
    }
}