<?php

namespace Digitonic\ApiTestSuite\Contracts;

use Illuminate\Database\Eloquent\Model;

interface GeneratesTestData
{
    /**
     * @return bool
     */
    public function viewableByOwnerOnly();

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
