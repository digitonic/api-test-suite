<?php

namespace Digitonic\ApiTestSuite\DataGeneration\Contracts;

interface Rule
{
    /**
     * @param array $payload
     * @param $field
     * @param array $rules
     * @param $newValueSeed
     * @param $class
     * @param $user
     * @return mixed
     */
    public function handle(array &$payload, $field, array $rules, $newValueSeed, $class, $user);
}
