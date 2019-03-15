<?php

namespace Digitonic\ApiTestSuite\Commands;

use Digitonic\ApiTestSuite\ApiTestSuiteServiceProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class Installer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'digitonic:api-test-suite:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install digitonic apit test suite';

    public function handle()
    {
        Artisan::call('vendor:publish', ['--provider' => ApiTestSuiteServiceProvider::class]);
    }
}
