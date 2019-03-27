<?php

namespace Digitonic\ApiTestSuite\DataGeneration\Rules;

use Digitonic\ApiTestSuite\DataGeneration\RuleParser;
use Faker\Generator;

abstract class Rule
{
    /**
     * @var Generator
     */
    protected $faker;
    /**
     * @var RuleParser
     */
    protected $parser;

    public function __construct()
    {
        $this->faker = resolve(Generator::class);
        $this->parser = new RuleParser();
    }
}
