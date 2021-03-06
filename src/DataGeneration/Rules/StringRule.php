<?php

namespace Digitonic\ApiTestSuite\DataGeneration\Rules;

use Digitonic\ApiTestSuite\DataGeneration\Contracts\Rule;
use Digitonic\ApiTestSuite\DataGeneration\Rules\Rule as BaseRule;

class StringRule extends BaseRule implements Rule
{
    public function handle(array &$payload, $field, array $rules, $newValueSeed, $class, $user)
    {
        $payload[$field] = preg_replace('#\W#', '', $this->faker->text());
    }
}
