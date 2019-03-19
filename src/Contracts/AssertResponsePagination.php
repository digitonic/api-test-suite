<?php

namespace Digitonic\ApiTestSuite\Contracts;

use Digitonic\ApiTestSuite\DataGenerator;
use Illuminate\Foundation\Testing\TestResponse;

interface AssertResponsePagination
{
    public function assertPagination(DataGenerator $dataGenerator);

    /**
     * @return bool
     */
    public function shouldPaginate();

    /**
     * @param null $data
     * @param array $params
     * @param array $headers
     * @return TestResponse
     */
    public function doAuthenticatedRequest($data = null, array $params = [], $headers = []);

    /**
     * @return int
     */
    public function entitiesPerPage();

    /**
     * @return float
     */
    public function entitiesNumber();
}
