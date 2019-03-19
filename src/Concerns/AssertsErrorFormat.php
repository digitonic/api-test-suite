<?php

namespace Digitonic\ApiTestSuite\Concerns;

use Illuminate\Foundation\Testing\TestResponse;
use Illuminate\Support\Facades\View;

trait AssertsErrorFormat
{
    /**
     * The template used allows regular expressions, e.g. in the default 400.blade.php template
     *
     * @param TestResponse $response
     * @param $status
     * @param array $data
     */
    protected function assertErrorFormat(TestResponse $response, $status, $data = [])
    {
        $response->assertStatus($status);
        $this->assertRegExp(
            "/" . View::file(
                config('digitonic.api-test-suite.templates.base_path') . 'errors/' . $status . '.blade.php',
                $data
            )->render() . "/",
            $response->getContent()
        );
    }
}
