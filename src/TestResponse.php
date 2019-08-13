<?php

namespace Digitonic\ApiTestSuite;
use Illuminate\Foundation\Testing\Assert as PHPUnit;

class TestResponse extends \Illuminate\Foundation\Testing\TestResponse
{
    /**
     * Assert that the response has the given status code.
     *
     * @param  int  $status
     * @return $this
     */
    public function assertStatus($status)
    {
        $actual = $this->getStatusCode();

        PHPUnit::assertTrue(
            $actual === $status,
            "Expected status code {$status} but received {$actual}. Response content: \n".
            print_r($this->responseContent(), true)
        );

        return $this;
    }

    public function responseContent()
    {
        $content = $this->getContent();

        $json = json_decode($content);

        if (json_last_error() === JSON_ERROR_NONE) {
            $content = $json;
        }

        return $content;
    }
}
