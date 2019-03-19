<?php

namespace Digitonic\ApiTestSuite\Contracts;

use Illuminate\Foundation\Testing\TestResponse;

interface InteractsWithApi
{
    /**
     * @param null $data
     * @param array $params
     * @param array $headers
     * @return TestResponse
     */
    public function doAuthenticatedRequest($data = null, array $params = [], $headers = []);

    /**
     * @param null $data
     * @param array $params
     * @param array $headers
     * @return TestResponse
     */
    public function doRequest($data = null, array $params = [], $headers = []);

    /**
     * @param TestResponse $response
     * @return array
     */
    public function getResponseData(TestResponse $response);

    /**
     * @param $entityData
     * @return array
     */
    public function jsonEncodeDataFields($entityData);

    /**
     * @return array
     */
    public function defaultHeaders();
}
