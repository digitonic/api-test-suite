<?php

namespace Digitonic\ApiTestSuite;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Mdoc\Campaigns\Models\Campaign;
use Mdoc\Users\Models\Team;

class DataGenerator
{
    /**
     * @var CRUDTestCase
     */
    private $testCase;
    /**
     * @var Collection
     */
    public $entities;

    public $user;

    protected $identifier;

    public function __construct(CRUDTestCase $testCase)
    {
        $this->testCase = $testCase;
        $this->entities = new Collection();
        $this->user = factory(config('digitonic.api-test-suite.api_user_class'))->state('crud')->create();
        $this->identifier = $testCase->identifier;
    }

    public function generateEntities()
    {
        // todo get rid of teams
        if (in_array($this->httpAction(), ['put', 'get', 'delete'])) {
            $entityData = $this->prepareEntityData();
            $this->entities = $this->createEntities($entityData);

            if ($this->numberOfEntitiesToGenerate() == $this->entitiesNumber() && $this->ownedClass()) {
                $team2 = $this->addTeamToUser();
                $entityData = $this->prepareEntityData();

                if ($this->ownedClass() === Team::class) {
                    $newOwningEntity = $team2;
                    $identifier = 'id';
                } else {
                    $identifier = $this->identifier->call($this);
                    $newOwningEntity = factory($this->ownedClass())->create();
                }

                $entityData[$this->ownedField()] = $newOwningEntity->$identifier;

                $this->createEntities($entityData);
            }
        }
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
     * @param string $attribute
     * @return mixed
     */
    protected function schemaHasAttribute(string $attribute)
    {
        $class = $this->entityClass();

        return Schema::hasColumn((new $class)->getTable(), $attribute);
    }

    /**
     * @param $entityData
     * @return array
     * @throws \ReflectionException
     */
    public function modifyCommentable(&$entityData)
    {
        $reflection = new \ReflectionClass($this->entityClass());

        // TODO this should be made more flexible to accommodate other types if required
        if ($reflection->hasMethod('commentable')) {
            $entityData['commentable_id']
                = Campaign::where($this->identifier->call($this), $entityData['campaign_' . $this->identifier->call($this)])->first()->id;
            $entityData['commentable_type'] = $entityData['type'];
            unset($entityData['campaign_' . $this->identifier->call($this)]);
            unset($entityData['type']);
        }
        return $entityData;
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

    private function createIncludedData($idKey, $entity)
    {
        foreach ($this->includedData() as $key => $class) {
            factory($class)->create(array_merge($this->entityData()[$key], [$idKey => $entity->id]));
        }
    }

    /**
     * @param $entity
     * @param $this
     */
    private function createManyToManyRelatedData($entity)
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

    private function getIdKey()
    {
        $idKey = explode('\\', $this->entityClass());
        return strtolower(array_last($idKey)) . '_id';
    }

    /**
     * @return int
     */
    protected function numberOfEntitiesToGenerate()
    {
        return $this->testCase->shouldReturnsStatus(Response::HTTP_NOT_FOUND) ? 1 : $this->entitiesNumber();
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

    /**
     * @return array
     */
    protected function prepareEntityData()
    {
        $entityData = $this->addTeamId($this->entityData(), $this->user->current_team_id);
        $this->unsetExternalData($entityData);
        return $entityData;
    }

    private function createEntities(array $entityData)
    {
        return factory($this->entityClass(), $this->numberOfEntitiesToGenerate())->create($entityData)->each(function ($entity) {
            $this->createIncludedData($this->getIdKey(), $entity);
            $this->createManyToManyRelatedData($entity);
        });
    }

    public function generateEntityNotOwnedByUser()
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
    public function generateUpdateData($data)
    {
        foreach ($data as $key => $datum) {
            if (strpos($key, $this->identifier->call($this)) === false) {
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
    public function getIdentifier()
    {
        $identifier = $this->identifier->call($this);
        return $this->entities->isEmpty() ? null : $this->entities->first()->$identifier;
    }

    public function ownedField()
    {
        return config('digitonic.api-test-suite.owned_class_field')->call($this);
    }

    public function __call($name, array $arguments)
    {
        $reflection = new \ReflectionMethod($this->testCase, $name);
        return $reflection->invoke($this->testCase, $arguments);
    }
}
