<?php

namespace Digitonic\ApiTestSuite\Concerns;

use Digitonic\ApiTestSuite\DataGenerator;
use Illuminate\Foundation\Testing\TestResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;

trait AssertResponsePagination
{
    public function assertPagination(DataGenerator $dataGenerator)
    {
        $entitiesNumber = $this->entitiesNumber();
        if ($this->shouldPaginate()) {
            $entitiesPerPage = $this->entitiesPerPage();
            // test page 1
            /** @var TestResponse $response */
            $response = $this->doAuthenticatedRequest(null, ['page' => 1, 'per_page' => $entitiesPerPage]);
            $this->assertPaginationFormat($response, $entitiesPerPage, $entitiesNumber);

            //test page 2
            $response = $this->doAuthenticatedRequest(null, ['page' => 2, 'per_page' => $entitiesPerPage]);
            $this->assertPaginationFormat($response, $entitiesNumber - $entitiesPerPage, $entitiesNumber);
        } else {
            $this->assertCount($entitiesNumber, $this->getResponseData(
                $this->doAuthenticatedRequest(null, [$dataGenerator->getIdentifier()])
            ));
        }
    }

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
    public function entitiesPerPage()
    {
        return config('digitonic.api-test-suite.entities_per_page');
    }

    /**
     * @return float
     */
    public function entitiesNumber()
    {
        return 1.5 * $this->entitiesPerPage();
    }
}
