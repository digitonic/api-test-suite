<?php

namespace Digitonic\ApiTestSuite\DataGeneration\Rules;

use Carbon\Carbon;
use Digitonic\ApiTestSuite\DataGeneration\Contracts\Rule;
use Digitonic\ApiTestSuite\DataGeneration\Rules\Rule as BaseRule;

class DateRule extends BaseRule implements Rule
{
    private $format;
    private $before;
    private $after;

    /**
     * DateRule constructor.
     * @param $format
     * @param $beforeTimestamp
     * @param $afterTimestamp
     */
    public function __construct($format, $afterTimestamp, $beforeTimestamp)
    {
        parent::__construct();
        $this->format = $format;
        $this->after = (int)$afterTimestamp;
        $this->before = (int)$beforeTimestamp;
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
        if (isset($payload[$field])) {
            $defaultTimestamp = $this->getTimestamp($payload[$field]);
            $after = max($defaultTimestamp,$this->after);
            $before = min($defaultTimestamp,$this->before);
        } else {
            $after = $this->after;
            $before = $this->before;
        }

        $payload[$field] = Carbon::createFromTimestamp(random_int($after, $before))->format($this->format);
    }

    protected function getTimestamp($dateString)
    {
        return Carbon::parse($dateString)->getTimestamp();
    }
}
