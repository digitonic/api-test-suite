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
    public function authorizingClass();

    /**
     * @return array
     */
    public function payload();

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
    public function resourceClass();
}
