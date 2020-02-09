<?php

namespace Minions\Task\Tests;

class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        $this->loadFactoriesUsing($app, __DIR__.'/factories/');
    }

    /**
     * Get package providers.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            \Laravie\Stream\Laravel\StreamServiceProvider::class,
            \Minions\MinionsServiceProvider::class,
            \Minions\Http\MinionsServiceProvider::class,
            \Minions\Task\TaskServiceProvider::class,

            \Orchestra\Canvas\Core\LaravelServiceProvider::class,
        ];
    }
}
