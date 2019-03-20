<?php

namespace Digitonic\ApiTestSuite\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Mdoc\Campaigns\Models\Campaign;
use Mdoc\Users\Models\Team;

trait GeneratesTestData
{
    /**
     * @var Collection
     */
    public $entities;

    protected function generateEntities($numberOfEntities)
    {
        // todo get rid of teams
        $payload = $this->preparePayload();
        $this->entities = $this->createEntities($payload, $numberOfEntities);

        if ($numberOfEntities == $this->entitiesNumber() && $this->authorizingClass()) {
            $team2 = $this->addTeamToUser();
            $payload = $this->preparePayload();

            if ($this->authorizingClass() === Team::class) {
                $newOwningEntity = $team2;
                $identifier = 'id';
            } else {
                $identifier = $this->identifier();
                $newOwningEntity = factory($this->authorizingClass())->create();
            }

            $payload[$this->ownedField()] = $newOwningEntity->$identifier;

            $this->createEntities($payload, 1);
        }
    }

    /**
     * @return array
     */
    protected function preparePayload()
    {
        $payload = $this->addTeamId($this->payload(), $this->user->current_team_id);
        $this->unsetExternalData($payload);
        return $payload;
    }

    protected function createEntities(array $payload, $numberOfEntities)
    {
        return factory($this->resourceClass(), $numberOfEntities)->create($payload)->each(function ($entity) {
            $this->createIncludedData($this->getIdKey(), $entity);
            $this->createManyToManyRelatedData($entity);
        });
    }

    /**
     * @param array $payload
     * @param $teamId
     * @return array
     */
    protected function addTeamId(array $payload, $teamId)
    {
        return $this->schemaHasAttribute('team_id')
            ? array_merge($payload, ['team_id' => $teamId])
            : $payload;
    }

    /**
     * @param $payload
     * @return array
     */
    protected function unsetExternalData(&$payload)
    {
        foreach (array_keys($this->includedData()) as $key) {
            unset($payload[$key]);
        }

        foreach ($this->manyToManyRelationships() as $class => $attribute) {
            unset($payload[$attribute]);
        }
    }

    protected function createIncludedData($idKey, $entity)
    {
        foreach ($this->includedData() as $key => $class) {
            factory($class)->create(array_merge($this->payload()[$key], [$idKey => $entity->id]));
        }
    }

    protected function getIdKey()
    {
        $idKey = explode('\\', $this->resourceClass());
        return strtolower(array_last($idKey)) . '_id';
    }

    /**
     * @param $entity
     * @param $this
     */
    protected function createManyToManyRelatedData($entity)
    {
        foreach ($this->manyToManyRelationships() as $class => $attribute) {
            foreach ($this->payload()[$attribute] as $id) {
                if (!$relatedEntity = $class::find($id)) {
                    factory($class)->create(['id' => $id]);
                }
            }
            $relation = Str::camel($attribute);
            $entity->$relation()->sync($this->payload()[$attribute]);
        }
    }

    /**
     * @param string $attribute
     * @return bool
     */
    protected function schemaHasAttribute($attribute)
    {
        $class = $this->resourceClass();

        return Schema::hasColumn((new $class)->getTable(), $attribute);
    }

    /**
     * @param $payload
     * @return array
     * @throws \ReflectionException
     */
    protected function modifyCommentable(&$payload)
    {
        $class = $this->resourceClass();
        $reflection = new \ReflectionClass($class);
        // TODO this should be made more flexible to accommodate other types if required
        if ($reflection->hasMethod('commentable')) {
            $payload['commentable_id']
                = Campaign::where($this->identifier(), $payload['campaign_' . $this->identifier()])->first()->id;
            $payload['commentable_type'] = $payload['type'];
            unset($payload['campaign_' . $this->identifier()]);
            unset($payload['type']);
        }
        return $payload;
    }

    /**
     * @return mixed
     */
    protected function addTeamToUser()
    {
        $team2 = factory(Team::class)->create([
            'owner_id' => $this->user->id
        ]);
        $this->user->teams->add($team2);
        return $team2;
    }

    protected function generateEntityNotOwnedByUser()
    {
        $data = $this->payload();
        $data[$this->ownedField()] = 100000000; // todo make generator
        foreach (array_keys($this->includedData()) as $key) {
            unset($data[$key]);
        }
        foreach ($this->manyToManyRelationships() as $attribute) {
            unset($data[$attribute]);
        }
        return factory($this->resourceClass())->create($data);
    }

    /**
     * @param $data
     * @return array
     */
    protected function generateUpdateData($data)
    {
        foreach ($data as $key => $datum) {
            if (strpos($key, $this->identifier()) === false) {
                if (is_array($datum)) {
                    $data[$key] = $this->generateUpdateData($datum);
                } else {
                    $data[$key] = preg_match('#1$#', $data[$key])
                        ? preg_replace('#.$#', '2', $data[$key])
                        : preg_replace('#.$#', '1', $data[$key]);
                }
                break;
            }
        }

        return $data;
    }

    /**
     * @return string|null
     */
    protected function getCurrentIdentifier()
    {
        $identifier = $this->identifier();
        return $this->entities->isEmpty() ? null : $this->entities->first()->$identifier;
    }

    protected function ownedField()
    {
        return config('digitonic.api-test-suite.owned_class_field')->call($this);
    }

    protected function identifier()
    {
        return config('digitonic.api-test-suite.identifier_field')->call($this);
    }
}
