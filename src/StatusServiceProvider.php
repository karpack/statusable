<?php

namespace Karpack\Statusable;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Karpack\Contracts\Statuses\StatusesManager;
use Karpack\Statusable\Commands\RegisterStatuses;
use Karpack\Statusable\Events\StatusChanged;
use Karpack\Statusable\Listeners\ExecuteStatusEvents;

class StatusServiceProvider extends ServiceProvider
{
    /**
     * Register the statuses repo/service
     * 
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/statusable.php', 'statusable');

        $this->registerService();

        $this->commands(RegisterStatuses::class);
    }

    /**
     * Registers the core status service
     * 
     * @return void
     */
    private function registerService()
    {
        $this->app->singleton(StatusesManager::class, function () {
            return new Statuses(
                Config::get('statusable.statusables'),
                Config::get('statusable.cache_statuses'),
                Config::get('statusable.cache_status_ids'),
                Config::get('statusable.cache_key'),
            );
        });

        $this->app->alias(StatusesManager::class, 'statuses');
    }

    /**
     * Bootstrap the status services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerMigrations();
        $this->registerConfigs();
        $this->registerEvents();
    }

    /**
     * Register the database migrations path
     * 
     * @return void
     */
    private function registerMigrations()
    {
        if (App::runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }
    }

    /**
     * Register status module configs
     * 
     * @return void
     */
    private function registerConfigs()
    {
        if (App::runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/statusable.php' => App::configPath('statusable.php'),
            ], 'statusable-config');
        }
    }

    /**
     * Register all the event listeners of this module
     * 
     * @return void
     */
    private function registerEvents()
    {
        Event::listen(StatusChanged::class, ExecuteStatusEvents::class);
    }
}
