<?php

namespace Digitonic\ApiTestSuite\DataGeneration;

use Digitonic\ApiTestSuite\DataGeneration\Factories\RuleFactory;

class RuleParser
{
    const RULE_ORDER = [
        'required' => 1,
        'before' => 2,
        'before_or_equal' => 2,
        'after' => 3,
        'after_or_equal' => 3,
        'max' => 98,
        'date_format' => 99,
        'between' => 99,
        'date_between' => 99,
    ];

    /**
     * @var string
     */
    private $raw;

    /**
     * @param $rules
     * @return RuleCollection
     */
    public function parse($rules)
    {
        $this->raw = $rules;

        $factory = new RuleFactory();
        $rulesArray = explode('|', $rules);

        usort(
            $rulesArray,
            function ($a, $b) {
                $aName = $this->getRuleName($a);
                $aWeight = isset(self::RULE_ORDER[$aName]) ? self::RULE_ORDER[$aName] : 50;
                $bName = $this->getRuleName($b);
                $bWeight = isset(self::RULE_ORDER[$bName]) ? self::RULE_ORDER[$bName] : 50;
                if ($aWeight == $bWeight) {
                    return 0;
                }
                return $aWeight > $bWeight ? 1 : -1;
            }
        );

        $ruleSet = new RuleCollection();
        foreach ($rulesArray as $rule) {
            $ruleName = $this->getRuleName($rule);
            $params = $this->getRuleParams($rule);
            $ruleSet->pushNew($factory->build($ruleName, $params));
        }

        return $ruleSet;
    }

    /**
     * @param $rule
     * @return string
     */
    protected function getRuleName($rule)
    {
        $rule = explode(':', $rule, 2);
        return $rule[0];
    }

    /**
     * @param $rule
     * @return string
     */
    protected function getRuleParams($rule)
    {
        $rule = explode(':', $rule, 2);
        return isset($rule[1]) ? $rule[1] : '';
    }

    /**
     * @return string
     */
    public function getRaw()
    {
        return $this->raw;
    }
}
