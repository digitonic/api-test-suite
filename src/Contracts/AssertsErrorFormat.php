<?php

namespace Digitonic\ApiTestSuite\Contracts;

use Illuminate\Foundation\Testing\TestResponse;

interface AssertsErrorFormat
{
    /**
     * The template used allows regular expressions, e.g. in the default 400.blade.php template
     *
     * @param TestResponse $response
     * @param $status
     * @param array $data
     */
    public function assertErrorFormat(TestResponse $response, $status, $data = []);
}
