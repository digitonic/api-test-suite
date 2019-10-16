<?php

namespace Digitonic\ApiTestSuite;

use Digitonic\ApiTestSuite\Commands\Installer;
use Illuminate\Support\ServiceProvider;

class ApiTestSuiteServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('digitonic.api-test-suite'),
            ], 'config');

            $this->publishes([
                __DIR__ . '/../templates/' => base_path('tests/templates/')
            ]);

            // Registering package commands.
             $this->commands([
                 Installer::class,
             ]);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'digitonic.api-test-suite');
    }
}
