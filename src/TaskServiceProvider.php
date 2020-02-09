<?php

namespace Minions\Task;

use Illuminate\Support\ServiceProvider;

class TaskServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/minions-task.php' => \config_path('minions-task.php'),
        ], 'config');

        $this->mergeConfigFrom(__DIR__.'/../config/minions-task.php', 'minions-task');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
