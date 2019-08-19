<?php

namespace Digitonic\ApiTestSuite\Contracts;

interface AssertsOutput
{
    /**
     * @return bool
     */
    public function expectsTimestamps();

    /**
     * @return array
     */
    public function expectedLinks();

    /**
     * This is the class through which the request authorization process validated
     *
     * @return bool
     */
    public function viewableByOwnerOnly();

    /**
     * @param array $data
     * @return array
     */
    public function expectedResourceData(array $data);
}
