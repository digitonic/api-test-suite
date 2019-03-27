<?php

namespace Digitonic\ApiTestSuite\DataGeneration\Rules;

use Digitonic\ApiTestSuite\DataGeneration\Contracts\Rule;

class AllowedRecipientsRule implements Rule
{
    public function handle(array &$payload, $field, array $rules, $newValueSeed, $class, $user)
    {
        $newPhoneNumber = '447123456789';
        do {
            foreach (str_split($newPhoneNumber) as $position => $number) {
                if ($position > 4) {
                    $newPhoneNumber[$position] = rand(0, 9);
                }
            }
        } while (!empty($class::where([$field => $newPhoneNumber])->first()));
        $payload[$field] = $newPhoneNumber;

        return $payload;
    }
}
