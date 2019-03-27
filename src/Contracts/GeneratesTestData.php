<?php

namespace Digitonic\ApiTestSuite\Contracts;

use Illuminate\Database\Eloquent\Model;

interface GeneratesTestData
{
    /**
     * @return string|null
     */
    public function authorizingClass();

    /**
     * @return string
     */
    public function resourceClass();

    /**
     * @return array
     */
    public function creationRules();

    /**
     * @return Model|string
     */
    public function createResource();

    /**
     * @return array
     */
    public function creationHeaders();
}
