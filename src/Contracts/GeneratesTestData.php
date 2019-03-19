<?php

namespace Digitonic\ApiTestSuite\Contracts;

interface GeneratesTestData
{
    /**
     * @return float
     */
    public function entitiesNumber();

    /**
     * @return string|null
     */
    public function ownedClass();

    /**
     * @return array
     */
    public function entityData();

    /**
     * @return array
     */
    public function includedData();

    /**
     * @return array
     */
    public function manyToManyRelationships();

    /**
     * @return string
     */
    public function entityClass();
}
