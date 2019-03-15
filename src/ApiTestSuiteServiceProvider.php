<?php

namespace Digitonic\ApiTestSuite;

use Digitonic\ApiTestSuite\Commands\Installer;
use Illuminate\Support\ServiceProvider;

class ApiTestSuiteServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/api-test-suite.php', 'digitonic.api-test-suite');

        $this->commands([
            Installer::class,
        ]);

        $this->publishes([
            __DIR__.'/../config/api-test-suite.php' => config_path('digitonic/api-test-suite.php'),
        ]);
    }
}
