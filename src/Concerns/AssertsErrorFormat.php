<?php

namespace Digitonic\ApiTestSuite\Concerns;

use Illuminate\Support\Facades\View;
use Illuminate\Testing\Assert as PHPUnit;
use Digitonic\ApiTestSuite\TestResponse;

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
        $this->checkRequiredResponseHeaders($response);
        PHPUnit::assertMatchesRegularExpression(
            "/" . trim(
                View::file(
                    config('digitonic.api-test-suite.templates.base_path') . 'errors/' . $status . '.blade.php',
                    $data
                )->with(

                        ['exception' => $response->exception
                    ]
                )->render()

) . "/",
            $response->getContent(),

            'Error response structure doesn\'t follow the template set up in '
            .config('digitonic.api-test-suite.templates.base_path').' errors/{errorStatusCode}.blade.php.'
        );
    }
}
