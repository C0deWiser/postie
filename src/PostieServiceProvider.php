<?php

namespace Codewiser\Postie;

use Codewiser\Postie\Console\InstallCommand;
use Codewiser\Postie\Console\PublishCommand;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class PostieServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerRoutes();
        $this->registerMigrations();
        $this->registerResources();
        $this->defineAssetPublishing();
        $this->offerPublishing();
        $this->registerCommands();
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (! defined('POSTIE_PATH')) {
            define('POSTIE_PATH', realpath(__DIR__.'/../'));
        }

        $this->mergeConfigFrom(__DIR__.'/../config/postie.php', 'postie');
    }

    /**
     * Register the Postie routes.
     */
    protected function registerRoutes(): void
    {
        $middlewares = config('postie.middleware', 'web');
        if (! is_array($middlewares)) {
            $middlewares = [$middlewares];
        }

        $auth = false;
        foreach ($middlewares as $middleware) {
            if ($middleware === 'auth' || str_starts_with($middleware, 'auth.')) {
                $auth = true;
            }
        }
        if (! $auth) {
            $middlewares[] = 'auth';
        }

        Route::group([
            'domain'     => config('postie.domain', null),
            'prefix'     => config('postie.path'),
            'middleware' => $middlewares,
            'as'         => 'postie.',
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
    }

    /**
     * Register the Postie migrations.
     */
    protected function registerMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    /**
     * Register the Postie resources.
     */
    protected function registerResources(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'postie');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'postie');
    }

    /**
     * Define the asset publishing configuration.
     */
    protected function defineAssetPublishing(): void
    {
        $this->publishes([
            POSTIE_PATH.'/public' => public_path('vendor/postie'),
        ], ['postie-assets', 'laravel-assets']);
    }

    /**
     * Set up the resource publishing groups
     */
    protected function offerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../stubs/PostieServiceProvider.php' => app_path('Providers/PostieServiceProvider.php'),
            ], 'postie-provider');

            $this->publishes([
                __DIR__.'/../config/postie.php' => config_path('postie.php'),
            ], 'postie-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'postie-migrations');

            $this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/postie'),
            ], 'postie-translations');
        }
    }

    /**
     * Register the package's commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {

            $commands = [
                InstallCommand::class,
                PublishCommand::class
            ];

            $this->commands($commands);
        }
    }
}
