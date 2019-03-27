<?php

namespace Digitonic\ApiTestSuite\DataGeneration\Rules;

use Digitonic\ApiTestSuite\DataGeneration\Contracts\Rule;

class NullRule implements Rule
{

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
        return;
    }
}
