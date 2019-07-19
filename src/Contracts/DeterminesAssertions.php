<?php

namespace Digitonic\ApiTestSuite\Contracts;

/**
 * Interface DeterminesAssertions
 * @package Digitonic\ApiTestSuite\Contracts
 */
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

    /**
     * @return bool
     */
    public function cannotBeDuplicated();
    
    /** 
     * @return array 
     */
    public function fieldsReplacement();
 }
