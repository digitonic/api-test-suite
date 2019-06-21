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
    protected function checkTransformerData(array $data, $identifier)
    {
        if ($this->isCollection($data)) {
            foreach ($data as $entity) {
                $this->assertIndividualEntityTransformerData($entity, $identifier);
            }
        } else {
            $this->assertIndividualEntityTransformerData($data, $identifier);
        }
    }

    protected function isCollection(array $data)
    {
        if (isset($this->isCollection)) {
            return $this->isCollection;
        }

        if (empty($data)) {
            $this->isCollection = false;
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

    /**
     * @param $data
     * @param $identifier
     * @param $included
     */
    protected function assertIndividualEntityTransformerData($data, $identifier)
    {
        $this->assertTransformerReplacesKeys(['id' => $identifier], $data);
        $this->assertDataIsPresent($data);
        $this->assertTimestamps($data);
        $this->assertLinks($data, $identifier);
    }

    /**
     * @param array $replacements
     * @param $data
     */
    protected function assertTransformerReplacesKeys(array $replacements, array $data)
    {
        if ($this->authorizingClass()) {
            foreach ($replacements as $original => $substitute) {
                $this->assertArrayNotHasKey($original, $data);
                $this->assertArrayHasKey($substitute, $data);
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
                $this->assertTrue($expected[$key] == $data[$key]);
            }
        }
    }

    /**
     * @param $data
     */
    protected function assertTimestamps(array $data)
    {
        if ($this->expectsTimestamps()) {
            $this->assertArrayHasKey('created_at', $data);
            $this->assertArrayHasKey('updated_at', $data);
            $this->assertIsString($data['created_at']);
            $this->assertIsString($data['updated_at']);
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
}
