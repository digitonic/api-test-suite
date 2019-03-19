<?php

namespace Digitonic\ApiTestSuite\Concerns;

use Digitonic\ApiTestSuite\DataGenerator;

trait AssertsTransformerData
{
    /**
     * @return bool
     */
    abstract protected function isListAction();

    /**
     * @param array $data
     * @param $identifier
     * @param DataGenerator $dataGenerator
     */
    protected function checkTransformerData(array $data, $identifier, DataGenerator $dataGenerator)
    {
        if ($this->isListAction()) {
            foreach ($data as $entity) {
                $this->assertIndividualEntityTransformerData($entity, $identifier, $dataGenerator);
            }
        } else {
            $this->assertIndividualEntityTransformerData($data, $identifier, $dataGenerator);
        }
    }

    /**
     * @param $data
     * @param $identifier
     * @param DataGenerator $dataGenerator
     */
    protected function assertIndividualEntityTransformerData($data, $identifier, DataGenerator $dataGenerator)
    {
        $this->assertTransformerReplacesKeys(['id' => $identifier], $data);
        $this->assertDataIsPresent($data, $dataGenerator);
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
     */
    protected function assertDataIsPresent(array $data, DataGenerator $dataGenerator)
    {
        $expected = $this->httpAction() === 'put'
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
}
