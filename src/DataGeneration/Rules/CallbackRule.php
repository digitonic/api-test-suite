<?php

namespace Digitonic\ApiTestSuite\DataGeneration\Rules;

use Digitonic\ApiTestSuite\DataGeneration\Contracts\Rule;
use Digitonic\ApiTestSuite\DataGeneration\Rules\Rule as BaseRule;

class CallbackRule extends BaseRule implements Rule
{
    /**
     * @var \Closure
     */
    private $callback;

    public function __construct(\Closure $callback)
    {
        parent::__construct();
        $this->callback = $callback;
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
        $payload[$field] = $this->callback->call($this, [
            'payload' => $payload,
            'field' => $field,
            'rules' => $rules,
            'newValueSeed' => $newValueSeed,
            'class' => $class,
            'user' =>$user
        ]);
    }
}
