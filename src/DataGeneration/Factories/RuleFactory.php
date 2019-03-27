<?php

namespace Digitonic\ApiTestSuite\DataGeneration\Factories;

use Carbon\Carbon;
use Digitonic\ApiTestSuite\DataGeneration\Rules\AllowedRecipientsRule;
use Digitonic\ApiTestSuite\DataGeneration\Rules\ArrayRule;
use Digitonic\ApiTestSuite\DataGeneration\Rules\BooleanRule;
use Digitonic\ApiTestSuite\DataGeneration\Rules\CallbackRule;
use Digitonic\ApiTestSuite\DataGeneration\Rules\DateRule;
use Digitonic\ApiTestSuite\DataGeneration\Rules\InRule;
use Digitonic\ApiTestSuite\DataGeneration\Rules\IntegerRule;
use Digitonic\ApiTestSuite\DataGeneration\Rules\MaxRule;
use Digitonic\ApiTestSuite\DataGeneration\Rules\NullRule;
use Digitonic\ApiTestSuite\DataGeneration\Rules\RequiredRule;
use Digitonic\ApiTestSuite\DataGeneration\Rules\StringRule;
use Digitonic\ApiTestSuite\DataGeneration\Rules\UniqueRule;
use Digitonic\ApiTestSuite\DataGeneration\Rules\UrlRule;

class RuleFactory
{
    public function build($rule, $parameters)
    {
        if (in_array($rule, array_keys(config('digitonic.api-test-suite.creation_rules')))){
            return new CallbackRule(config('digitonic.api-test-suite.creation_rules')[$rule]);
        }

        switch($rule){
            case 'string':
            case 'alpha_num':
                return new StringRule();
            case 'boolean':
                return new BooleanRule();
            case 'integer':
                return new IntegerRule();
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
            case 'date_format':
                return new DateRule($parameters, 1, Carbon::parse('+80years')->getTimestamp());
            case 'after':
                return new DateRule('Y-m-d H:i:s', Carbon::parse($parameters)->getTimestamp(), Carbon::parse('+80years')->getTimestamp());
            case 'before':
                return new DateRule('Y-m-d H:i:s', 1, Carbon::parse($parameters)->getTimestamp());
            default:
                return new NullRule();
        }
    }
}
