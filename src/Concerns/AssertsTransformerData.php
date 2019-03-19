<?php

namespace Digitonic\ApiTestSuite\Concerns;

trait AssertsTransformerData
{
    /**
     * @param array $data
     * @param $identifier
     * @param $httpAction
     * @param $included
     */
    protected function checkTransformerData(array $data, $identifier, $httpAction, $included)
    {
        if ($this->isCollection($data)) {
            foreach ($data as $entity) {
                $this->assertIndividualEntityTransformerData($entity, $identifier, $httpAction, $included);
            }
        } else {
            $this->assertIndividualEntityTransformerData($data, $identifier, $httpAction, $included);
        }
    }

    /**
     * @param $data
     * @param $identifier
     * @param $httpAction
     * @param $included
     */
    protected function assertIndividualEntityTransformerData($data, $identifier, $httpAction, $included)
    {
        $this->assertTransformerReplacesKeys(['id' => $identifier], $data);
        $this->assertDataIsPresent($data, $httpAction, $included);
        $this->assertTimestamps($data);
        $this->assertLinks($data, $identifier);
    }

    /**
     * @param array $replacements
     * @param $data
     */
    protected function assertTransformerReplacesKeys(array $replacements, array $data)
    {
        if ($this->ownedClass()) {
            foreach ($replacements as $original => $substitute) {
                $this->assertArrayNotHasKey($original, $data);
                $this->assertArrayHasKey($substitute, $data);
            }
        }
    }

    /**
     * @param array $data
     * @param $httpAction
     * @param $includedData
     */
    protected function assertDataIsPresent(array $data, $httpAction, $includedData)
    {
        $expected = $httpAction === 'put'
            ? $this->generateUpdateData($this->transformerData())
            : $this->transformerData();

        foreach (array_keys($includedData) as $included) {
            $expected[$included] = ['data' => $expected[$included]];
        }

        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $data);
            $this->assertTrue($expected[$key] == $data[$key]);
        }
    }

    /**
     * @param $data
     */
    protected function assertTimestamps(array $data)
    {
        if ($this->entityHasTimestamps()) {
            $this->assertArrayHasKey('created_at', $data);
            $this->assertArrayHasKey('updated_at', $data);
        }
    }

    /**
     * @param array $data
     * @param $identifier
     */
    protected function assertLinks(array $data, $identifier)
    {
        foreach ($this->requiredLinks() as $rel => $routeName) {
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
        if (empty($data)){
            return false;
        }

        return array_reduce($data, function($carry, $item){
            return $carry && is_array($item);
        }, true);
    }
}
