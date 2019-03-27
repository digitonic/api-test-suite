<?php

namespace Digitonic\ApiTestSuite\DataGeneration;

use Digitonic\ApiTestSuite\DataGeneration\Factories\RuleFactory;

class RuleParser
{
    const RULE_ORDER = [
        'required' => 1,
        'after' => 2,
        'before' => 2,
        'date_format' => 98,
        'max' => 99
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

        usort($rulesArray, function($a, $b){
            $aName = $this->getRuleName($a);
            $aWeight = isset(self::RULE_ORDER[$aName]) ? self::RULE_ORDER[$aName] : 50;
            $bName = $this->getRuleName($b);
            $bWeight = isset(self::RULE_ORDER[$bName]) ? self::RULE_ORDER[$bName] : 50;
            if ($aWeight == $bWeight){
                return 0;
            }
            return $aWeight > $bWeight ? 1 : -1;
        });

        // todo reorder: Make MaxRule as last, 'after' and 'before' before 'date_format'

        $ruleSet = new RuleCollection();
        foreach ($rulesArray as $rule) {
            $ruleName = $this->getRuleName($rule);
            $params = $this->getRuleParams($rule);
            $ruleSet->push($factory->build($ruleName, $params));
        }


        return $ruleSet;
    }

    /**
     * @return string
     */
    public function getRaw()
    {
        return $this->raw;
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
}
