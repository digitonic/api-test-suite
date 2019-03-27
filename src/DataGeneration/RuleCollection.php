<?php

namespace Digitonic\ApiTestSuite\DataGeneration;

use Digitonic\ApiTestSuite\DataGeneration\Contracts\Rule;
use Digitonic\ApiTestSuite\DataGeneration\Rules\RequiredRule;
use Illuminate\Support\Collection;

class RuleCollection extends Collection
{
    /**
     * @var bool
     */
    private $required = false;

    public function generate(array &$payload, $field, array $rules, $newSeedValue, $class, $user)
    {
        $toBeApplied = $this->filter(function ($item) {
            if ($item instanceof RequiredRule){
                $this->required = true;
            } else {
                return true;
            }
        });

        if (/*$this->required &&*/ strpos($field, '.') === false){
            $toBeApplied->each(function (Rule $rule) use (&$payload, $field, $rules, $newSeedValue, $class, $user){
                $rule->handle($payload, $field, $rules, $newSeedValue, $class, $user);
            });
        }
    }

    /**
     * @param bool $required
     */
    public function setRequired($required)
    {
        $this->required = $required;
    }
}
