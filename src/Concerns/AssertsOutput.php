<?php

namespace Digitonic\ApiTestSuite\Concerns;

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
        $this->assertTransformerReplacesKeys(['id'], $data);
        $this->assertDataIsPresent($data);
        $this->assertTimestamps($data, $updatedAt);
        $this->assertLinks($data, $identifier);
    }

    /**
     * @param array $replacements
     * @param $data
     */
    protected function assertTransformerReplacesKeys(array $replacements, array $data)
    {
        if ($this->authorizingClass()) {
            foreach ($replacements as $original) {
                $this->assertArrayNotHasKey($original, $data, 'Field \'id\' should not be present in public facing data');
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
                    . print_r($expected[$key],true) . '\''
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
            if (!empty($updatedAt)){
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
}
