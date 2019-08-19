<?php

namespace Digitonic\ApiTestSuite\DataGeneration\Factories;

use Carbon\Carbon;
use Digitonic\ApiTestSuite\DataGeneration\Rules\AllowedRecipientsRule;
use Digitonic\ApiTestSuite\DataGeneration\Rules\ArrayRule;
use Digitonic\ApiTestSuite\DataGeneration\Rules\BetweenRule;
use Digitonic\ApiTestSuite\DataGeneration\Rules\BooleanRule;
use Digitonic\ApiTestSuite\DataGeneration\Rules\CallbackRule;
use Digitonic\ApiTestSuite\DataGeneration\Rules\DateRule;
use Digitonic\ApiTestSuite\DataGeneration\Rules\InRule;
use Digitonic\ApiTestSuite\DataGeneration\Rules\IntegerRule;
use Digitonic\ApiTestSuite\DataGeneration\Rules\MaxRule;
use Digitonic\ApiTestSuite\DataGeneration\Rules\NullRule;
use Digitonic\ApiTestSuite\DataGeneration\Rules\NumericRule;
use Digitonic\ApiTestSuite\DataGeneration\Rules\RequiredRule;
use Digitonic\ApiTestSuite\DataGeneration\Rules\StringRule;
use Digitonic\ApiTestSuite\DataGeneration\Rules\UniqueRule;
use Digitonic\ApiTestSuite\DataGeneration\Rules\UrlRule;

class RuleFactory
{
    public function build($rule, $parameters)
    {
        if (in_array($rule, array_keys(config('digitonic.api-test-suite.creation_rules')))) {
            return new CallbackRule(config('digitonic.api-test-suite.creation_rules')[$rule]);
        }

        switch ($rule) {
            case 'string':
            case 'alpha_num':
                return new StringRule();
            case 'boolean':
                return new BooleanRule();
            case 'integer':
                return new IntegerRule();
            case 'numeric':
                return new NumericRule();
            case 'allowed_recipients':
                return new AllowedRecipientsRule();
            case 'array':
                return new ArrayRule();
            case 'unique':
                return new UniqueRule();
            case 'required':
                return new RequiredRule();
            case 'url':
                return new UrlRule();
            case 'in':
                return new InRule(explode(',', $parameters));
            case 'max':
                return New MaxRule($parameters);
            case 'between':
                $params = explode(',', $parameters);
                return new BetweenRule($params[0], $params[1]);
            case 'date_format':
                $rule = new DateRule();
                $rule->setFormat($parameters);
                return $rule;
            case 'after':
                $rule = new DateRule();
                $rule->setAfter(Carbon::parse($parameters)->getTimestamp(), true);
                return $rule;
            case 'after_or_equal':
                $rule = new DateRule();
                $rule->setAfter(Carbon::parse($parameters)->getTimestamp(), false);
                return $rule;
            case 'before':
                $rule = new DateRule();
                $rule->setBefore(Carbon::parse($parameters)->getTimestamp(), true);
                return $rule;
            case 'before_or_equal':
                $rule = new DateRule();
                $rule->setBefore(Carbon::parse($parameters)->getTimestamp(), false);
                return $rule;
            case 'date_between':
                $params = explode(',', $parameters);
                $rule = new DateRule();
                $rule->setBefore(Carbon::parse($params[1])->getTimestamp(), false);
                $rule->setAfter(Carbon::parse($params[0])->getTimestamp(), false);
                return $rule;
            default:
                return new NullRule();
        }
    }
}
