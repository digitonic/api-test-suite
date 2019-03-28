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
    private $strictAfter;
    private $strictBefore;

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
        // todo set defaults if not set
        $this->after = empty($this->after) ? $this->getTimestamp('-80years') : $this->after;
        $this->before = empty($this->before) ? $this->getTimestamp('+80years') : $this->before;

        if (isset($payload[$field])) {
            $defaultTimestamp = $this->getTimestamp($payload[$field]);
            $after = max($defaultTimestamp, $this->after);
            $before = min($after, $this->before);
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

    /**
     * @return mixed
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @param mixed $format
     */
    public function setFormat($format): void
    {
        $this->format = $format;
    }

    /**
     * @return mixed
     */
    public function getBefore()
    {
        return $this->before;
    }

    /**
     * @param mixed $before
     */
    public function setBefore($before, $strict = true): void
    {
        $this->before = $before;
        $this->strictBefore = $strict;
    }

    /**
     * @return mixed
     */
    public function getAfter()
    {
        return $this->after;
    }

    /**
     * @param mixed $after
     */
    public function setAfter($after, $strict = true): void
    {
        $this->after = $after;
        $this->strictAfter = $strict;
    }

    /**
     * @return mixed
     */
    public function getStrictAfter()
    {
        return $this->strictAfter;
    }

    /**
     * @param mixed $strictAfter
     */
    public function setStrictAfter($strictAfter): void
    {
        $this->strictAfter = $strictAfter;
    }

    /**
     * @return mixed
     */
    public function getStrictBefore()
    {
        return $this->strictBefore;
    }

    /**
     * @param mixed $strictBefore
     */
    public function setStrictBefore($strictBefore): void
    {
        $this->strictBefore = $strictBefore;
    }
}
