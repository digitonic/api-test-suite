<?php

namespace Digitonic\ApiTestSuite\Concerns;

use Digitonic\ApiTestSuite\DataGeneration\RuleParser;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Collection;

trait GeneratesTestData
{
    /**
     * @var Collection
     */
    public $entities;

    public $payload;

    /**
     * @param $numberOfEntities
     * @param $httpAction
     * @param $baseUser
     * @param $otherUser
     */
    public function generateEntities($numberOfEntities, $httpAction, $baseUser, $otherUser)
    {
        $this->payload = $this->generatePayload($baseUser);
        if (in_array($httpAction, ['put', 'get', 'delete'])) {
            $this->entities->push($this->generateSingleEntity($baseUser, $this->payload));
        }
        for ($i = 1; $i < $numberOfEntities; $i++) {
            $this->entities->push($this->generateSingleEntity($baseUser));
        }
        if ($httpAction === 'get' && $this->authorizingClass()) {
            $this->entities->push($this->generateSingleEntity($otherUser));
        }
    }

    protected function generatePayload($user)
    {
        $payload = [];
        $rules = $this->creationRules();
        foreach ($rules as $field => $rule) {
            $ruleParser = new RuleParser();
            $ruleSet = $ruleParser->parse($rule);
            $ruleSet->generate($payload, $field, $rules, random_int(0, 999999999), $this->resourceClass(), $user);
        }

        return $payload;
    }

    public function generateEntityOverApi(array $payload, $user)
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $response = $this->actingAs($user)->call(
            'post',
            route($this->createResource()),
            $payload,
            [],
            [],
            $this->creationHeaders()
        );
        $this->withMiddleware(ThrottleRequests::class);

        $id = json_decode($response->getContent(), true)['data'][$this->identifier()];

        return $this->resourceClass()::where([$this->identifier() => $id])->first();
    }

    /**
     * @param $payload
     * @param $user
     * @return array
     * @throws \Exception
     */
    protected function generateUpdateData($payload, $user)
    {
        foreach ($this->creationRules() as $field => $rule) {
            if (strpos($field, $this->identifier()) === false) {
                $ruleParser = new RuleParser();
                $ruleSet = $ruleParser->parse($rule);
                $ruleSet->generate(
                    $payload,
                    $field,
                    $this->creationRules(),
                    random_int(0, 999999999),
                    $this->resourceClass(),
                    $user
                );
            }
        }

        return $payload;
    }

    /**
     * @return string|null
     */
    protected function getCurrentIdentifier()
    {
        $identifier = $this->identifier();
        return $this->entities->isEmpty() ? null : $this->entities->first()->$identifier;
    }

    protected function identifier()
    {
        return config('digitonic.api-test-suite.identifier_field')->call($this);
    }

    protected function generateSingleEntity($user, $payload = null)
    {
        if (!$payload) {
            $payload = $this->generatePayload($user);
        }

        if (is_string($this->createResource())) {
            return $this->generateEntityOverApi($payload, $user);
        } else {
            return $this->createResource()->call($this, ['payload' => $payload, 'user' => $user]);
        }
    }
}
