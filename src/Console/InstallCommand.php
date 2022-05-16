<?php

namespace Codewiser\Postie\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'postie:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install all of the Postie resources';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->comment('Publishing Postie Service Provider...');
        $this->callSilent('vendor:publish', ['--tag' => 'postie-provider']);

        $this->comment('Publishing Postie Assets...');
        $this->callSilent('vendor:publish', ['--tag' => 'postie-assets']);

        $this->comment('Publishing Postie Configuration...');
        $this->callSilent('vendor:publish', ['--tag' => 'postie-config']);

        $this->registerPostieServiceProvider();

        $this->info('Postie scaffolding installed successfully.');
    }

    /**
     * Register the Folks service provider in the application configuration file.
     *
     * @return void
     */
    protected function registerPostieServiceProvider()
    {
        $namespace = Str::replaceLast('\\', '', $this->laravel->getNamespace());

        $appConfig = file_get_contents(config_path('app.php'));

        if (Str::contains($appConfig, $namespace.'\\Providers\\PostieServiceProvider::class')) {
            return;
        }

        file_put_contents(config_path('app.php'), str_replace(
            "{$namespace}\\Providers\EventServiceProvider::class,".PHP_EOL,
            "{$namespace}\\Providers\EventServiceProvider::class,".PHP_EOL."        {$namespace}\Providers\PostieServiceProvider::class,".PHP_EOL,
            $appConfig
        ));

        file_put_contents(app_path('Providers/PostieServiceProvider.php'), str_replace(
            "namespace App\Providers;",
            "namespace {$namespace}\Providers;",
            file_get_contents(app_path('Providers/PostieServiceProvider.php'))
        ));
    }
}
