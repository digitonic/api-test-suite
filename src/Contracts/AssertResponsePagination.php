<?php

namespace Digitonic\ApiTestSuite\Contracts;

use Illuminate\Foundation\Testing\TestResponse;

interface AssertResponsePagination
{
    /**
     * @param TestResponse $response
     * @param $expectedCount
     */
    public function assertPaginationFormat(TestResponse $response, $expectedCount, $expectedTotal);
    /**
     * @return int
     */
    public function entitiesPerPage();

    /**
     * @return float
     */
    public function entitiesNumber();
}
