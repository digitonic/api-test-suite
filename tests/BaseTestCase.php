<?php

namespace Digitonic\ApiTestSuite\Tests;

use Digitonic\ApiTestSuite\ApiTestSuiteServiceProvider;
use Orchestra\Testbench\TestCase;

class BaseTestCase extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            ApiTestSuiteServiceProvider::class
        ];
    }

    protected function getPackageAliases($app)
    {
        return [];
    }
}
