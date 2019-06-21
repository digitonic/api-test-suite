<?php

namespace Digitonic\ApiTestSuite\DataGeneration\Rules;

use Digitonic\ApiTestSuite\DataGeneration\Contracts\Rule;
use Digitonic\ApiTestSuite\DataGeneration\Rules\Rule as BaseRule;

class ArrayRule extends BaseRule implements Rule
{
    public function handle(array &$payload, $field, array $rules, $newValueSeed, $class, $user)
    {
        $payload[$field] = [];
        $relatedFields = collect($rules)->filter(
            function ($item, $index) use ($field) {
                return strpos($index, $field . '.') !== false;
            }
        )->toArray();

        if (count($relatedFields)) {
            foreach ($relatedFields as $subField => $rules) {
                $subField = str_replace($field . '.', '', $subField);
                $subField = $subField === '*' ? 0 : $subField;
                $ruleSet = $this->parser->parse($rules);
                $ruleSet->generate($payload[$field], $subField, $relatedFields, $newValueSeed, $class, $user);
            }
        } else {
            $payload[$field][0] = 0;
        }
    }
}
