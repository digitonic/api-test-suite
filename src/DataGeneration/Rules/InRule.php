<?php

namespace Digitonic\ApiTestSuite\DataGeneration\Rules;

use Digitonic\ApiTestSuite\DataGeneration\Contracts\Rule;
use Digitonic\ApiTestSuite\DataGeneration\Rules\Rule as BaseRule;

class InRule extends BaseRule implements Rule
{
    private $array;

    /**
     * InRule constructor.
     */
    public function __construct($array)
    {
        parent::__construct();
        $this->array = $array;
    }


    /**
     * @param array $payload
     * @param $field
     * @param array $rules
     * @param $newValueSeed
     * @param $class
     * @return mixed
     * @throws \Exception
     */
    public function handle(array &$payload, $field, array $rules, $newValueSeed, $class, $user)
    {
        $payload[$field] = $this->array[random_int(0, sizeof($this->array) - 1)];
    }
}
