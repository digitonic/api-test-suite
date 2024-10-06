<?php

namespace Digitonic\ApiTestSuite\Concerns;

use Illuminate\Testing\TestResponse;
use phpDocumentor\Reflection\Types\Boolean;

trait AssertsOutput
{
    protected $isCollection;

    protected $updateData;

    /**
     * @param array $data
     * @param $identifier
     * @param $included
     */
    protected function checkTransformerData(array $data, $identifier, $updatedAt = null)
    {
        if ($this->isCollection($data)) {
            foreach ($data as $entity) {
                $this->assertIndividualEntityTransformerData($entity, $identifier);
            }
        } else {
            $this->assertIndividualEntityTransformerData($data, $identifier, $updatedAt);
        }
    }

    /**
     * @param $data
     * @param $identifier
     * @param $included
     */
    protected function assertIndividualEntityTransformerData($data, $identifier, $updatedAt = null)
    {
        $this->assertTransformerReplacesKeys($data);
        $this->assertDataIsPresent($data);
        $this->assertTimestamps($data, $updatedAt);
        $this->assertLinks($data, $identifier);
    }

    /**
     * @param array $replacements
     * @param $data
     */
    protected function assertTransformerReplacesKeys(array $data)
    {
        if (!empty($this->fieldsReplacement())) {
            foreach ($this->fieldsReplacement() as $original => $replacement) {
                $this->assertArrayNotHasKey(
                    $original,
                    $data,
                    'Field '
                    . $original
                    . ' should not be present in public facing data. Please make sure that '
                    . $replacement . ' is used instead or change the `shouldReplaceFields` method implementation'
                );
                $this->assertArrayHasKey(
                    $replacement,
                    $data,
                    'Field ' . $replacement . ' should be present in public facing data instead of '
                    . $original . ' or change the `shouldReplaceFields` method implementation'
                );
            }
        }
    }

    /**
     * @param array $data
     */
    protected function assertDataIsPresent(array $data)
    {
        $expected = $this->expectedResourceData($data);
        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $data);
            if (!$this->isCollection) {
                $this->assertTrue(
                    $expected[$key] == $data[$key],
                    'The output data \'' . print_r($data[$key], true)
                    . '\'for the key \'' . $key . '\' doesn\'t match the expected \''
                    . print_r($expected[$key], true) . '\''
                );
            }
        }
    }

    /**
     * @param $data
     */
    protected function assertTimestamps(array $data, ?string $updatedAt)
    {
        if ($this->expectsTimestamps()) {
            $this->assertArrayHasKey('created_at', $data);
            $this->assertArrayHasKey('updated_at', $data);
            $this->assertIsString($data['created_at']);
            $this->assertIsString($data['updated_at']);
            if (!empty($updatedAt)) {
                $this->assertNotEquals(
                    $updatedAt,
                    $data['updated_at'],
                    'The \'updated_at\' timestamp should change on update of the resource.'
                    . ' Make sure it is done by calling touch() on that entity if it\'s not updated directly.'
                );
            }
        }
    }

    /**
     * @param array $data
     * @param $identifier
     */
    protected function assertLinks(array $data, $identifier)
    {
        foreach ($this->expectedLinks() as $rel => $routeName) {
            $this->assertContains(
                [
                    'rel' => $rel,
                    'uri' => route($routeName, $data[$identifier])
                ],
                $data['links']
            );
        }
    }

    protected function isCollection(array $data)
    {
        if (isset($this->isCollection)) {
            return $this->isCollection;
        }

        if (empty($data)) {
            $this->isCollection = false;
            return $this->isCollection;
        }

        $this->isCollection = array_reduce(
            $data,
            function ($carry, $item) {
                return $carry && is_array($item);
            },
            true
        );

        return $this->isCollection;
    }

    public function checkRequiredResponseHeaders(TestResponse $response): bool
    {
        return collect(
            array_keys($this->requiredResponseHeaders())
        )->reduce(
            function ($carry, $index) use ($response){
                return $carry && $response->assertHeader($index, $this->requiredResponseHeaders()[$index]);
            },
            true
        );
    }

    protected function requiredResponseHeaders()
    {
        return config('digitonic.api-test-suite.required_response_headers');
    }
}
