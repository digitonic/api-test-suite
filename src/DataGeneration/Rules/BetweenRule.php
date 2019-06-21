<?php

namespace Digitonic\ApiTestSuite\DataGeneration\Rules;

use Digitonic\ApiTestSuite\DataGeneration\Contracts\Rule;
use Digitonic\ApiTestSuite\DataGeneration\Rules\Rule as BaseRule;

class BetweenRule extends BaseRule implements Rule
{
    private $min;

    private $max;

    public function __construct($min, $max)
    {
        $this->min = $min;
        $this->max = $max;
    }

    public function handle(array &$payload, $field, array $rules, $newValueSeed, $class, $user)
    {
        $type = gettype($payload[$field]);

        if ($type === 'double') {
            $type = 'float';
        }

        $var = rand($this->min, $this->max);
        settype($var, $type);
        $payload[$field] = $var;
    }
}
