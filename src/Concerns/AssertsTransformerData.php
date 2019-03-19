<?php

namespace Digitonic\ApiTestSuite\Concerns;

use Digitonic\ApiTestSuite\DataGenerator;

trait AssertsTransformerData
{
    /**
     * @param array $data
     * @param $identifier
     * @param DataGenerator $dataGenerator
     * @param $httpAction
     */
    protected function checkTransformerData(array $data, $identifier, DataGenerator $dataGenerator, $httpAction)
    {
        if ($this->isCollection($data)) {
            foreach ($data as $entity) {
                $this->assertIndividualEntityTransformerData($entity, $identifier, $dataGenerator, $httpAction);
            }
        } else {
            $this->assertIndividualEntityTransformerData($data, $identifier, $dataGenerator, $httpAction);
        }
    }

    /**
     * @param $data
     * @param $identifier
     * @param DataGenerator $dataGenerator
     * @param $httpAction
     */
    protected function assertIndividualEntityTransformerData($data, $identifier, DataGenerator $dataGenerator, $httpAction)
    {
        $this->assertTransformerReplacesKeys(['id' => $identifier], $data);
        $this->assertDataIsPresent($data, $dataGenerator, $httpAction);
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
     * @param DataGenerator $dataGenerator
     * @param $httpAction
     */
    protected function assertDataIsPresent(array $data, DataGenerator $dataGenerator, $httpAction)
    {
        $expected = $httpAction === 'put'
            ? $dataGenerator->generateUpdateData($this->transformerData())
            : $this->transformerData();

        foreach (array_keys($this->includedData()) as $included) {
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
