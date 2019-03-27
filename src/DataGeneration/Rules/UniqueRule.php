<?php

namespace Digitonic\ApiTestSuite\DataGeneration\Rules;

use Digitonic\ApiTestSuite\DataGeneration\Contracts\Rule;

class UniqueRule implements Rule
{
    public function handle(array &$payload, $field, array $rules, $newValueSeed, $class, $user)
    {
        do {
            $new = random_int(0, 999999999);
            $payload[$field] = (string)$new;
        } while ($class::where([$field => $new])->first());

        return $payload;
    }
}
