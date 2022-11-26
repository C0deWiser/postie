<?php

namespace Codewiser\Postie;

use Codewiser\Postie\Console\InstallCommand;
use Codewiser\Postie\Console\PublishCommand;
use Codewiser\Postie\Contracts\Channelizationable;
use Codewiser\Postie\Contracts\Postie;
use Codewiser\Postie\Contracts\PostieAssets;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class PostieServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
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
     *
     * @return void
     */
    public function register()
    {
        if (!defined('POSTIE_PATH')) {
            define('POSTIE_PATH', realpath(__DIR__ . '/../'));
        }

        $this->mergeConfigFrom(__DIR__ . '/../config/postie.php', 'postie');

        $this->app->singleton(Postie::class, function () {
            return new PostieService();
        });

        $this->app->singleton(PostieService::class, function () {
            return new PostieService();
        });
    }

    /**
     * Register the Postie routes.
     *
     * @return void
     */
    protected function registerRoutes()
    {
        Route::group([
            'domain' => config('postie.domain', null),
            'prefix' => config('postie.path'),
            'middleware' => array_merge(config('postie.middleware', 'web'), ['auth']),
            'as' => 'postie.',
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        });
    }

    /**
     * Register the Postie migrations.
     *
     * @return void
     */
    protected function registerMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * Register the Postie resources.
     *
     * @return void
     */
    protected function registerResources()
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'postie');
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'postie');
    }

    /**
     * Define the asset publishing configuration.
     *
     * @return void
     */
    protected function defineAssetPublishing()
    {
        $this->publishes([
            POSTIE_PATH . '/public' => public_path('vendor/postie'),
            POSTIE_PATH . '/resources/lang' => resource_path('lang/vendor/postie'),
        ], ['postie-assets', 'laravel-assets']);
    }

    /**
     * Setup the resource publishing groups
     *
     * @return void
     */
    protected function offerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../stubs/PostieServiceProvider.php' => app_path('Providers/PostieServiceProvider.php'),
            ], 'postie-provider');

            $this->publishes([
                __DIR__ . '/../config/postie.php' => config_path('postie.php'),
            ], 'postie-config');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'postie-migrations');
        }
    }

    /**
     * Register the package's commands.
     *
     * @return void
     */
    protected function registerCommands()
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
