<?php

namespace Digitonic\ApiTestSuite\Concerns;

use Illuminate\Foundation\Testing\TestResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;

trait AssertPagination
{
    /**
     * @param $response
     * @param $expectedCount
     */
    protected function assertPaginationFormat(TestResponse $response, $expectedCount, $expectedTotal)
    {
        $response->assertStatus(Response::HTTP_OK);
        $this->assertCount(
            $expectedCount,
            json_decode($response->getContent(), true)['data']
        );
        $this->assertPaginationResponseStructure($response, $expectedTotal);
    }

    /**
     * @param TestResponse $response
     * @param $total
     */
    protected function assertPaginationResponseStructure(TestResponse $response, $total)
    {
        $this->assertRegExp(
            "/" . View::file(
                config('digitonic.api-test-suite.templates.base_path') . 'pagination/pagination.blade.php',
                [
                    'total' => $total
                ]
            )->render() . "/",
            $response->getContent()
        );
    }

    /**
     * @return int
     */
    protected function entitiesPerPage()
    {
        return config('digitonic.api-test-suite.entities_per_page');
    }

    /**
     * @return float
     */
    protected function entitiesNumber()
    {
        return 1.5 * $this->entitiesPerPage();
    }
}
