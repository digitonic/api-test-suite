<?php

namespace Digitonic\ApiTestSuite\Contracts;

interface InteractsWithApi
{
    /**
     * @return string
     */
    public function routeName();

    /**
     * @return string
     */
    public function httpAction();
}
