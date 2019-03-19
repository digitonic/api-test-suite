<?php

namespace Digitonic\ApiTestSuite\Contracts;

interface CRUDTestCase
{
    /**
     * @return string
     */
    public function routeName();

    /**
     * @return string
     */
    public function httpAction();

    /**
     * @return bool
     */
    public function shouldPaginate();

    /**
     * @return array
     */
    public function requiredFields();

    /**
     * @return array
     */
    public function requiredHeaders();

    /**
     * @return array
     */
    public function statusCodes();

    /**
     * @return array
     */
    public function entityData();

    /**
     * @return string
     */
    public function entityClass();

    /**
     * @return array
     */
    public function jsonFields();

    /**
     * @return array
     */
    public function includedData();

    /**
     * @return array
     */
    public function manyToManyRelationships();
}
