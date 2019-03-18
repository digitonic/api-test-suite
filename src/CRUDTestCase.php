<?php

namespace Digitonic\ApiTestSuite;

use Illuminate\Foundation\Testing\TestResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Mdoc\Campaigns\Models\Campaign;
use Mdoc\Users\Models\Team;
use Tests\TestCase;

abstract class CRUDTestCase extends TestCase
{
    // TODO write a read.me with assumptions of the package about app structure

    /**
     * @var array
     */
    protected $defaultHeaders;

    protected $user;
    /**
     * @var Collection
     */
    protected $entities;

    /**
     * float
     */
    protected $entitiesNumber;

    /**
     * float
     */
    protected $entitiesPerPage;

    /**
     * @var \Closure
     */
    protected $identifier;

    /**
     * @var \Closure
     */
    protected $identifierGenerator;

    /**
     * @var \Closure
     */
    protected $ownedField;

    /**
     * @return string
     */
    abstract protected function routeName();

    /**
     * @return string
     */
    abstract protected function httpAction();

    /**
     * @return bool
     */
    abstract protected function entityHasTimestamps();

    /**
     * @return bool
     */
    abstract protected function shouldPaginate();

    /**
     * @return array
     */
    abstract protected function requiredFields();

    /**
     * @return array
     */
    abstract protected function requiredHeaders();

    /**
     * @return array
     */
    abstract protected function requiredLinks();

    /**
     * @return array
     */
    abstract protected function statusCodes();

    /**
     * @return array
     */
    abstract protected function entityData();

    /**
     * @return string
     */
    abstract protected function entityClass();

    /**
     * @return array
     */
    abstract protected function jsonFields();

    /**
     * @return string|null
     */
    abstract protected function ownedClass();

    /**
     * @return array
     */
    abstract protected function includedData();

    /**
     * @return array
     */
    abstract protected function transformerData();

    /**
     * @return array
     */
    abstract protected function manyToManyRelationships();

    public function setUp(): void
    {
        parent::setUp();
        $this->defaultHeaders = config('digitonic.api-test-suite.default_headers');
        $this->user = factory(config('digitonic.api-test-suite.api_user_class'))->state('crud')->create();
        $this->entitiesPerPage = config('digitonic.api-test-suite.entities_per_page');
        $this->entitiesNumber = $this->entitiesPerPage * 1.5;
        $this->identifier = config('digitonic.api-test-suite.identifier_field');
        $this->identifierGenerator = config('digitonic.api-test-suite.identifier_faker');
        $this->ownedField = config('digitonic.api-test-suite.owned_class_field');
        $this->entities = new Collection();
    }

    public function runBaseApiTestSuite()
    {
        $this->generateEntities();
        $this->assertCantUseRouteWithoutAuthenticating();
        $this->assertNotFound();
        $this->assertFailedValidationForRequiredFields();
        $this->assertAccessIsForbidden();
        $this->assertRequiredHeaders();
        $this->assertCreate();
        $this->assertUpdate();
        $this->assertRetrieve();
        $this->assertListAll();
        $this->assertDelete();
    }

    protected function generateEntities()
    {
        // todo make it dependent on user defined factories
        // todo get rid of teams
        if (in_array($this->httpAction(), ['put', 'get', 'delete'])) {
            $number = $this->shouldReturnsStatus(Response::HTTP_NOT_FOUND) ? 1 : $this->entitiesNumber;

            $entityData = $this->addTeamId($this->entityData(), $this->user->current_team_id);
            foreach (array_keys($this->includedData()) as $key) {
                unset($entityData[$key]);
            }

            foreach ($this->manyToManyRelationships() as $class => $attribute) {
                unset($entityData[$attribute]);
            }

            $this->entities = factory($this->entityClass(), $number)->create($entityData)->each(function ($entity) {
                $idKey = explode('\\', $this->entityClass());
                $idKey = strtolower(array_last($idKey)) . '_id';
                foreach ($this->includedData() as $key => $class) {
                    factory($class)->create(array_merge($this->entityData()[$key], [$idKey => $entity->id]));
                }
                foreach ($this->manyToManyRelationships() as $class => $attribute) {
                    foreach ($this->entityData()[$attribute] as $id) {
                        if (!$relatedEntity = $class::find($id)) {
                            factory($class)->create(['id' => $id]);
                        }
                    }
                    $relation = Str::camel($attribute);
                    $entity->$relation()->sync($this->entityData()[$attribute]);
                }
            });

            if ($number == $this->entitiesNumber && $this->ownedClass()) {
                $team2 = factory(Team::class)->create([
                    'owner_id' => $this->user->id
                ]);
                $this->user->teams->add($team2);

                $entityData = $this->addTeamId($this->entityData(), $team2->id);
                foreach (array_keys($this->includedData()) as $key) {
                    unset($entityData[$key]);
                }

                foreach ($this->manyToManyRelationships() as $attribute) {
                    unset($entityData[$attribute]);
                }

                if ($this->ownedClass()) {
                    if ($this->ownedClass() == Team::class) {
                        $entityData['team_id'] = $team2->id;
                    } else {
                        $identifier = $this->identifier->call($this);
                        $entityData[$this->ownedField->call($this)] =
                            factory($this->ownedClass())->create()->$identifier;
                    }
                }

                factory($this->entityClass(), $number)->create($entityData)->each(function ($entity) {
                    $idKey = explode('\\', $this->entityClass());
                    $idKey = strtolower(array_last($idKey)) . '_id';
                    foreach ($this->includedData() as $key => $class) {
                        factory($class)->create(array_merge($this->entityData()[$key], [$idKey => $entity->id]));
                    }
                });
            }
        }
    }

    protected function assertCantUseRouteWithoutAuthenticating()
    {
        if ($this->shouldReturnsStatus(Response::HTTP_UNAUTHORIZED)) {
            Auth::logout();
            /** @var TestResponse $response */
            $response = $this->doRequest($this->entityData(), [$this->getIdentifier()]);
            $this->assertErrorFormat($response, Response::HTTP_UNAUTHORIZED);
        }
    }

    protected function assertNotFound()
    {
        if ($this->shouldReturnsStatus(Response::HTTP_NOT_FOUND)) {
            $response = $this->doAuthenticatedRequest(null, [$this->identifierGenerator->call($this)]);
            $this->assertErrorFormat($response, Response::HTTP_NOT_FOUND);
        }
    }

    protected function assertFailedValidationForRequiredFields()
    {
        if ($this->shouldReturnsStatus(Response::HTTP_UNPROCESSABLE_ENTITY)) {
            foreach ($this->requiredFields() as $key) {
                if (!isset($this->entityData()[$key])) {
                    $this->fail('The field ' . $key . ' is required');
                }
                $this->assertRequiredField($this->entityData(), $key, !is_array($this->entityData()[$key]));
            }
        }
    }

    /**
     * @param $data
     * @param $key
     * @param bool $assertValidationResponse
     */
    protected function assertRequiredField($data, $key, $assertValidationResponse)
    {
        unset($data[$key]);

        /** @var TestResponse $response */
        $response = $this->doAuthenticatedRequest($data, [$this->getIdentifier()]);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        if ($assertValidationResponse) {
            $this->assertErrorFormat($response, Response::HTTP_UNPROCESSABLE_ENTITY, [
                'fieldName' => $key,
                'formattedFieldName' => str_replace('_', ' ', $key)
            ]);
        }
    }

    protected function assertAccessIsForbidden()
    {
        if ($this->shouldReturnsStatus(Response::HTTP_FORBIDDEN)) {
            $data = $this->entityData();
            $data[$this->ownedField->call($this)] = 100000000; // todo make generator
            foreach (array_keys($this->includedData()) as $key) {
                unset($data[$key]);
            }
            foreach ($this->manyToManyRelationships() as $attribute) {
                unset($data[$attribute]);
            }
            $entity = factory($this->entityClass())->create($data);
            /** @var TestResponse $response */
            $response = $this->doRequest($data, [$entity[$this->identifier->call($this)]]);
            $this->assertErrorFormat($response, Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * @throws \ReflectionException
     */
    protected function assertCreate()
    {
        if ($this->shouldReturnsStatus(Response::HTTP_CREATED)) {
            /** @var TestResponse $response */
            $response = $this->doAuthenticatedRequest();
            $response->assertStatus(Response::HTTP_CREATED);
            $this->checkTransformerData($response);
            $this->assertCreatedOnlyOnce();
        }
    }

    /**
     * @throws \ReflectionException
     */
    protected function assertCreatedOnlyOnce()
    {
        $this->doAuthenticatedRequest();
        $entityData = $this->entityData();
        $entityData = $this->jsonEncodeDataFields($entityData);
        foreach ($entityData as $key => $item) {
            if (in_array($key, array_keys($this->includedData()))) {
                unset($entityData[$key]);
            }
        }

        foreach ($this->manyToManyRelationships() as $attribute) {
            unset($entityData[$attribute]);
        }

        $this->modifyCommentable($entityData);

        $this->assertCount(1, $this->entityClass()::where($entityData)->get());
    }

    protected function assertUpdate()
    {
        if ($this->shouldReturnsStatus(Response::HTTP_ACCEPTED)) {
            $data = $this->generateUpdateData($this->entityData());
            foreach (array_keys($this->includedData()) as $included) {
                unset($data[$included]);
            }
            /** @var TestResponse $response */
            $response = $this->doAuthenticatedRequest($data, [$this->getIdentifier()]);
            $response->assertStatus(Response::HTTP_ACCEPTED);
            $this->checkTransformerData($response);
            $this->assertCount(1, $this->entityClass()::where([
                $this->identifier->call($this) => $this->getIdentifier()
            ])->get());
        }
    }

    protected function assertRetrieve()
    {
        if ($this->shouldReturnsStatus(Response::HTTP_OK) && !$this->isListAction()) {
            $response = $this->doAuthenticatedRequest(null, [$this->getIdentifier()]);
            $response->assertStatus(Response::HTTP_OK);
            $this->checkTransformerData($response);
        }
    }

    protected function assertListAll()
    {
        if ($this->shouldReturnsStatus(Response::HTTP_OK) && $this->isListAction()) {
            $response = $this->doAuthenticatedRequest(null, [$this->getIdentifier()]);
            $response->assertStatus(Response::HTTP_OK);
            $this->checkTransformerData($response);
            $this->assertPagination();
        }
    }

    protected function assertDelete()
    {
        if ($this->shouldReturnsStatus(Response::HTTP_NO_CONTENT)) {
            $response = $this->doAuthenticatedRequest(null, [$this->getIdentifier()]);
            $response->assertStatus(Response::HTTP_NO_CONTENT);
            $this->assertEmpty($response->getContent());
            $this->assertNull($this->entityClass()::find($this->entities->first()->id));
        }
    }

    /**
     * @param TestResponse $response
     */
    protected function checkTransformerData(TestResponse $response)
    {
        $data = $this->getResponseData($response);

        if (!$this->isListAction()) {
            $this->assertIndividualEntityTransformerData($data);
        } else {
            foreach ($data as $entity) {
                $this->assertIndividualEntityTransformerData($entity);
            }
        }
    }

    /**
     * @return bool
     */
    protected function isListAction()
    {
        return !$this->shouldReturnsStatus(Response::HTTP_NOT_FOUND) && $this->httpAction() == 'get';
    }

    /**
     * @param $data
     */
    protected function assertIndividualEntityTransformerData($data)
    {
        $this->assertTransformerReplacesKeys(['id' => $this->identifier->call($this)], $data);
        $this->assertDataIsPresent($data);
        $this->assertTimestamps($data);
        $this->assertLinks($data);
    }

    /**
     * @param array $replacements
     * @param $data
     */
    protected function assertTransformerReplacesKeys(array $replacements, $data)
    {
        if ($this->ownedClass()) {
            foreach ($replacements as $original => $substitute) {
                $this->assertArrayNotHasKey($original, $data);
                $this->assertArrayHasKey($substitute, $data);
            }
        }
    }

    /**
     * @param $data
     */
    protected function assertDataIsPresent($data)
    {
        $expected = $this->httpAction() === 'put'
            ? $this->generateUpdateData($this->transformerData())
            : $this->transformerData();

        foreach (array_keys($this->includedData()) as $included) {
            $expected[$included] = ['data' => $expected[$included]];
        }

        if (isset($expected['team_id'])) {
            $identifier = $this->identifier->call($this);
            $expected['team_' . $this->identifier->call($this)] = Team::find($expected['team_id'])->$identifier;
            unset($expected['team_id']);
            $this->assertTransformerReplacesKeys(['team_id' => 'team_' . $this->identifier->call($this)], $data);
        }
        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $data);
            $this->assertTrue($expected[$key] == $data[$key]);
        }
    }

    /**
     * @param $data
     */
    protected function assertTimestamps($data)
    {
        if ($this->entityHasTimestamps()) {
            $this->assertArrayHasKey('created_at', $data);
            $this->assertArrayHasKey('updated_at', $data);
        }
    }

    /**
     * @param $data
     */
    protected function assertLinks($data)
    {
        foreach ($this->requiredLinks() as $rel => $routeName) {
            $this->assertContains([
                'rel' => $rel,
                'uri' => route($routeName, $data[$this->identifier->call($this)])
            ], $data['links']);
        }
    }

    /**
     * @param int $statusCode
     * @return bool
     */
    protected function shouldReturnsStatus(int $statusCode)
    {
        return collect($this->statusCodes())->contains($statusCode);
    }

    /**
     * @return string|null
     */
    protected function getIdentifier()
    {
        $identifier = $this->identifier->call($this);
        return $this->entities->isEmpty() ? null : $this->entities->first()->$identifier;
    }

    /**
     * @param null $data
     * @param array $params
     * @param array $headers
     * @return mixed
     */
    protected function doAuthenticatedRequest($data = null, array $params = [], $headers = [])
    {
        return $this->actingAs($this->user)->doRequest($data, $params, $headers);
    }

    /**
     * @param null $data
     * @param array $params
     * @param array $headers
     * @return TestResponse
     */
    protected function doRequest($data = null, array $params = [], $headers = [])
    {
        return $this->call(
            $this->httpAction(),
            route($this->routeName(), $params),
            $data ?? $this->entityData(),
            [],
            [],
            empty($headers) ? $this->defaultHeaders : $headers
        );
    }

    /**
     * @param TestResponse $response
     * @return array
     */
    protected function getResponseData(TestResponse $response)
    {
        $data = json_decode($response->getContent(), true)['data'];

        if (empty($data)) {
            $this->fail('The response data is empty');
        }

        return $data;
    }

    /**
     * @return array
     */
    protected function generateUpdateData($data)
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

    protected function assertPagination()
    {
        if ($this->shouldPaginate()) {
            // test page 1
            $response = $this->doAuthenticatedRequest(null, ['page' => 1, 'per_page' => $this->entitiesPerPage]);
            $response->assertStatus(Response::HTTP_OK);
            $this->assertCount($this->entitiesPerPage, json_decode($response->getContent(), true)['data']);
            $this->assertPaginationResponseStructure($response);

            //test page 2
            $response = $this->doAuthenticatedRequest(null, ['page' => 2, 'per_page' => $this->entitiesPerPage]);
            $response->assertStatus(Response::HTTP_OK);
            $this->assertCount(
                $this->entitiesNumber - $this->entitiesPerPage,
                json_decode($response->getContent(), true)['data']
            );
            $this->assertPaginationResponseStructure($response);
        } else {
            $this->assertCount($this->entitiesNumber, $this->getResponseData(
                $this->doAuthenticatedRequest(null, [$this->getIdentifier()])
            ));
        }
    }

    protected function assertRequiredHeaders()
    {
        foreach ($this->requiredHeaders() as $header => $value) {
            $headers = $this->requiredHeaders();
            unset($headers[$header]);

            /** If $headers is empty, defaults will be used*/
            if (empty($headers)) {
                $headers['not_empty'] = 'not_empty';
            }
            $response = $this->doAuthenticatedRequest(null, [$this->getIdentifier()], $headers);
            $this->assertErrorFormat($response, Response::HTTP_BAD_REQUEST, []);

            if ($value) {
                $headers[$header] = '123456789';
                $response = $this->doAuthenticatedRequest(null, [$this->getIdentifier()], $headers);
                $this->assertErrorFormat($response, Response::HTTP_BAD_REQUEST);
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
        if ($this->schemaHasAttribute('team_id')) {
            $entityData = array_merge($entityData, ['team_id' => $teamId]);
        }
        return $entityData;
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
     */
    protected function jsonEncodeDataFields($entityData)
    {
        foreach ($entityData as $key => $value) {
            if (in_array($key, $this->jsonFields())) {
                $entityData[$key] = json_encode($value);
            }
        }
        return $entityData;
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
                = Campaign::where($this->identifier->call($this), $entityData['campaign_' . $this->identifier->call($this)])->first()->id;
            $entityData['commentable_type'] = $entityData['type'];
            unset($entityData['campaign_' . $this->identifier->call($this)]);
            unset($entityData['type']);
        }
        return $entityData;
    }

    /**
     * The template used allows regular expressions, e.g. in the default 400.blade.php template
     *
     * @param TestResponse $response
     * @param $status
     * @param array $data
     */
    protected function assertErrorFormat(TestResponse $response, $status, $data = [])
    {
        $response->assertStatus($status);
        $this->assertRegExp(
            "/" . View::file(
                config('digitonic.api-test-suite.templates.base_path') . 'errors/' . $status . '.blade.php',
                $data
            )->render() . "/",
            $response->getContent()
        );
    }

    /**
     * @param TestResponse $response
     */
    protected function assertPaginationResponseStructure(TestResponse $response)
    {
        $this->assertRegExp(
            "/" . View::file(
                config('digitonic.api-test-suite.templates.base_path') . 'pagination/pagination.blade.php',
                [
                    'total' => $this->entitiesNumber
                ]
            )->render() . "/",
            $response->getContent()
        );
    }
}
