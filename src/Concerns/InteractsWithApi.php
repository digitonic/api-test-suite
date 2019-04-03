<?php

namespace Digitonic\ApiTestSuite\Concerns;

use Illuminate\Foundation\Testing\TestResponse;

trait InteractsWithApi
{
    public $user;

    /**
     * @param null $data
     * @param array $params
     * @param array $headers
     * @return TestResponse
     */
    protected function doAuthenticatedRequest($data, array $params = [], $headers = [])
    {
        return $this->actingAs($this->user, 'api')->doRequest($data, $params, $headers);
    }

    /**
     * @param array $data
     * @param array $params
     * @param array $headers
     * @return TestResponse
     */
    protected function doRequest($data, array $params = [], $headers = [])
    {
        return $this->call(
            $this->httpAction(),
            route($this->routeName(), $params),
            $data,
            [],
            [],
            empty($headers) ? $this->defaultHeaders() : $headers
        );
    }

    /**
     * @param TestResponse $response
     * @return array
     */
    protected function getResponseData(TestResponse $response)
    {
        $data = json_decode($response->getContent(), true);

        if (!isset($data['data'])) {
            $this->fail('The response data is empty');
        }

        return $data['data'];
    }

    protected function defaultHeaders()
    {
        return config('digitonic.api-test-suite.default_headers');
    }
}
