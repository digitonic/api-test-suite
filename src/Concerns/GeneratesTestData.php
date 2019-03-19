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
        $entityData = $this->prepareEntityData();
        $this->entities = $this->createEntities($entityData, $numberOfEntities);

        if ($numberOfEntities == $this->entitiesNumber() && $this->ownedClass()) {
            $team2 = $this->addTeamToUser();
            $entityData = $this->prepareEntityData();

            if ($this->ownedClass() === Team::class) {
                $newOwningEntity = $team2;
                $identifier = 'id';
            } else {
                $identifier = $this->identifier();
                $newOwningEntity = factory($this->ownedClass())->create();
            }

            $entityData[$this->ownedField()] = $newOwningEntity->$identifier;

            $this->createEntities($entityData, 1);
        }
    }

    /**
     * @return array
     */
    protected function prepareEntityData()
    {
        $entityData = $this->addTeamId($this->entityData(), $this->user->current_team_id);
        $this->unsetExternalData($entityData);
        return $entityData;
    }

    protected function createEntities(array $entityData, $numberOfEntities)
    {
        return factory($this->entityClass(), $numberOfEntities)->create($entityData)->each(function ($entity) {
            $this->createIncludedData($this->getIdKey(), $entity);
            $this->createManyToManyRelatedData($entity);
        });
    }

    /**
     * @param array $entityData
     * @param $teamId
     * @return array
     */
    protected function addTeamId(array $entityData, $teamId)
    {
        return $this->schemaHasAttribute('team_id')
            ? array_merge($entityData, ['team_id' => $teamId])
            : $entityData;
    }

    /**
     * @param $entityData
     * @return array
     */
    protected function unsetExternalData(&$entityData)
    {
        foreach (array_keys($this->includedData()) as $key) {
            unset($entityData[$key]);
        }

        foreach ($this->manyToManyRelationships() as $class => $attribute) {
            unset($entityData[$attribute]);
        }
    }

    protected function createIncludedData($idKey, $entity)
    {
        foreach ($this->includedData() as $key => $class) {
            factory($class)->create(array_merge($this->entityData()[$key], [$idKey => $entity->id]));
        }
    }

    protected function getIdKey()
    {
        $idKey = explode('\\', $this->entityClass());
        return strtolower(array_last($idKey)) . '_id';
    }

    /**
     * @param $entity
     * @param $this
     */
    protected function createManyToManyRelatedData($entity)
    {
        foreach ($this->manyToManyRelationships() as $class => $attribute) {
            foreach ($this->entityData()[$attribute] as $id) {
                if (!$relatedEntity = $class::find($id)) {
                    factory($class)->create(['id' => $id]);
                }
            }
            $relation = Str::camel($attribute);
            $entity->$relation()->sync($this->entityData()[$attribute]);
        }
    }

    /**
     * @param string $attribute
     * @return bool
     */
    protected function schemaHasAttribute($attribute)
    {
        $class = $this->entityClass();

        return Schema::hasColumn((new $class)->getTable(), $attribute);
    }

    /**
     * @param $entityData
     * @return array
     * @throws \ReflectionException
     */
    protected function modifyCommentable(&$entityData)
    {
        $reflection = new \ReflectionClass($this->entityClass());

        // TODO this should be made more flexible to accommodate other types if required
        if ($reflection->hasMethod('commentable')) {
            $entityData['commentable_id']
                = Campaign::where($this->identifier(), $entityData['campaign_' . $this->identifier()])->first()->id;
            $entityData['commentable_type'] = $entityData['type'];
            unset($entityData['campaign_' . $this->identifier()]);
            unset($entityData['type']);
        }
        return $entityData;
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
        $data = $this->entityData();
        $data[$this->ownedField()] = 100000000; // todo make generator
        foreach (array_keys($this->includedData()) as $key) {
            unset($data[$key]);
        }
        foreach ($this->manyToManyRelationships() as $attribute) {
            unset($data[$attribute]);
        }
        return factory($this->entityClass())->create($data);
    }

    /**
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
