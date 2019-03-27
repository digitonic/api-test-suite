<?php

namespace Digitonic\ApiTestSuite\DataGeneration\Rules;

use Digitonic\ApiTestSuite\DataGeneration\Contracts\Rule;
use Digitonic\ApiTestSuite\DataGeneration\Rules\Rule as BaseRule;

class MaxRule extends BaseRule implements Rule
{
    private $max;

    /**
     * MaxRule constructor.
     * @param $max
     */
    public function __construct($max)
    {
        parent::__construct();
        $this->max = (int)$max;
    }


    /**
     * @param array $payload
     * @param $field
     * @param array $rules
     * @param $newValueSeed
     * @param $class
     * @return mixed
     */
    public function handle(array &$payload, $field, array $rules, $newValueSeed, $class, $user)
    {
        $payload[$field] = substr($payload[$field], 0, $this->max);
    }
}
