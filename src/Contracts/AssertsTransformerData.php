<?php

namespace Digitonic\ApiTestSuite\Contracts;

interface AssertsTransformerData
{
    /**
     * @return bool
     */
    public function entityHasTimestamps();

    /**
     * @return array
     */
    public function requiredLinks();

    /**
     * @return string|null
     */
    public function ownedClass();

    /**
     * @return array
     */
    public function transformerData();
}
