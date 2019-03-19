<?php

namespace Digitonic\ApiTestSuite\Contracts;

interface CRUDTestCase
{
    /**
     * @return array
     */
    public function requiredFields();

    /**
     * @return array
     */
    public function requiredHeaders();

    /**
     * @return string
     */
    public function entityClass();

    /**
     * @return array
     */
    public function manyToManyRelationships();
}
