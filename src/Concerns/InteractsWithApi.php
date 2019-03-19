<?php

namespace Digitonic\ApiTestSuite\Concerns;

use Illuminate\Foundation\Testing\TestResponse;

trait InteractsWithApi
{
    /**
     * @param null $data
     * @param array $params
     * @param array $headers
     * @return TestResponse
     */
    public function doAuthenticatedRequest($data = null, array $params = [], $headers = [])
    {
        return $this->actingAs($this->dataGenerator->user)->doRequest($data, $params, $headers);
    }

    /**
     * @param null $data
     * @param array $params
     * @param array $headers
     * @return TestResponse
     */
    public function doRequest($data = null, array $params = [], $headers = [])
    {
        return $this->call(
            $this->httpAction(),
            route($this->routeName(), $params),
            $data ?? $this->entityData(),
            [],
            [],
            empty($headers) ? $this->defaultHeaders() : $headers
        );
    }

    /**
     * @param TestResponse $response
     * @return array
     */
    public function getResponseData(TestResponse $response)
    {
        $data = json_decode($response->getContent(), true)['data'];

        if (empty($data)) {
            $this->fail('The response data is empty');
        }

        return $data;
    }

    /**
     * @param $entityData
     * @return array
     */
    public function jsonEncodeDataFields($entityData)
    {
        foreach ($entityData as $key => $value) {
            if (in_array($key, $this->jsonFields())) {
                $entityData[$key] = json_encode($value);
            }
        }
        return $entityData;
    }

    public function defaultHeaders()
    {
        return config('digitonic.api-test-suite.default_headers');
    }
}