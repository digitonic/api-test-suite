<?php

namespace Digitonic\ApiTestSuite\Contracts;

interface DeterminesAssertions
{
    /**
     * @return bool
     */
    public function shouldAssertPaginate();

    /**
     * @return array
     */
    public function statusCodes();
 }
