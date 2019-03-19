<?php

namespace Digitonic\ApiTestSuite\Contracts;

interface InteractsWithApi
{
    /**
     * @return string
     */
    public function routeName();

    /**
     * @return array
     */
    public function jsonFields();

    /**
     * @return array
     */
    public function entityData();

    /**
     * @return string
     */
    public function httpAction();
}
