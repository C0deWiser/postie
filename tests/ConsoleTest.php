<?php

namespace Codewiser\Postie\Tests;

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;

class ConsoleTest extends TestCase
{
    public function test_install_and_publish_commands_are_registered(): void
    {
        $commands = array_keys($this->app->make(ConsoleKernel::class)->all());

        $this->assertContains('postie:install', $commands);
        $this->assertContains('postie:publish', $commands);
    }

    public function test_publish_command_publishes_assets(): void
    {
        $this->artisan('postie:publish')
            ->assertExitCode(0);

        $this->assertFileExists(public_path('vendor/postie/mix-manifest.json'));
    }
}