<?php

namespace Digitonic\ApiTestSuite;

use Illuminate\Foundation\Testing\TestResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Mdoc\Campaigns\Models\Campaign;
use Mdoc\Concerns\HasUuid;
use Mdoc\Users\Models\Team;
use Tests\TestCase;

abstract class CRUDTestCase extends TestCase
{
    use HasUuid;

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

    protected $identifier;

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
    abstract protected function authorizationBearer();

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

            if ($number == $this->entitiesNumber && $this->authorizationBearer()) {
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

                if ($this->authorizationBearer()) {
                    if ($this->authorizationBearer() == $this->entityClass()) {
                        $entityData['team_id'] = $team2->id;
                    } else {
                        $identifier = $this->identifier;
                        $entityData[$this->getAuthorizationBearerKey()] =
                            factory($this->authorizationBearer())->create()->$identifier;
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
            $response = $this->doRequest($this->entityData(), [$this->getUuid()]);
            $response->assertStatus(Response::HTTP_UNAUTHORIZED);
            $this->assertErrorResponseContent(
                $response,
                Response::HTTP_UNAUTHORIZED,
                'Unauthenticated'
            );
        }
    }

    protected function assertNotFound()
    {
        if ($this->shouldReturnsStatus(Response::HTTP_NOT_FOUND)) {
            $response = $this->doAuthenticatedRequest(null, [$this->generateUuid()]);
            $response->assertStatus(Response::HTTP_NOT_FOUND);
            $this->assertErrorResponseContent(
                $response,
                Response::HTTP_NOT_FOUND,
                'The entity you are looking for was not found'
            );
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
        $response = $this->doAuthenticatedRequest($data, [$this->getUuid()]);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        if ($assertValidationResponse) {
            $this->assertValidationResponseContent(
                $response,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                $key
            );
        }
    }

    protected function assertAccessIsForbidden()
    {
        if ($this->shouldReturnsStatus(Response::HTTP_FORBIDDEN)) {
            $data = $this->entityData();
            $data[$this->getAuthorizationBearerKey()] = 100000000;
            foreach (array_keys($this->includedData()) as $key) {
                unset($data[$key]);
            }
            foreach ($this->manyToManyRelationships() as $attribute) {
                unset($data[$attribute]);
            }
            $entity = factory($this->entityClass())->create($data);
            /** @var TestResponse $response */
            $response = $this->doRequest($data, [$entity[$this->identifier]]);
            $response->assertStatus(Response::HTTP_FORBIDDEN);
            $this->assertErrorResponseContent(
                $response,
                Response::HTTP_FORBIDDEN,
                'This action is unauthorized'
            );
        }
    }

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
            $response = $this->doAuthenticatedRequest($data, [$this->getUuid()]);
            $response->assertStatus(Response::HTTP_ACCEPTED);
            $this->checkTransformerData($response);
            $this->assertCount(1, $this->entityClass()::where([$this->identifier => $this->getUuid()])->get());
        }
    }

    protected function assertRetrieve()
    {
        if ($this->shouldReturnsStatus(Response::HTTP_OK) && !$this->isListAction()) {
            $response = $this->doAuthenticatedRequest(null, [$this->getUuid()]);
            $response->assertStatus(Response::HTTP_OK);
            $this->checkTransformerData($response);
        }
    }

    protected function assertListAll()
    {
        if ($this->shouldReturnsStatus(Response::HTTP_OK) && $this->isListAction()) {
            $response = $this->doAuthenticatedRequest(null, [$this->getUuid()]);
            $response->assertStatus(Response::HTTP_OK);
            $this->checkTransformerData($response);
            $this->assertPagination();
        }
    }

    protected function assertDelete()
    {
        if ($this->shouldReturnsStatus(Response::HTTP_NO_CONTENT)) {
            $response = $this->doAuthenticatedRequest(null, [$this->getUuid()]);
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
        $this->assertTransformerReplacesKeys(['id' => $this->identifier], $data);
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
        if ($this->authorizationBearer()) {
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
            $identifier = $this->identifier;
            $expected['team_uuid'] = Team::find($expected['team_id'])->$identifier;
            unset($expected['team_id']);
            $this->assertTransformerReplacesKeys(['team_id' => 'team_uuid'], $data);
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
                'uri' => route($routeName, $data[$this->identifier])
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
    protected function getUuid()
    {
        $identifier = $this->identifier;
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
            if (strpos($key, $this->identifier) === false) {
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
            $response = $this->doAuthenticatedRequest(null, ['page' => 1, 'per_page' => $this->entitiesPerPage]);
            $response->assertStatus(Response::HTTP_OK);
            $this->assertCount($this->entitiesPerPage, json_decode($response->getContent(), true)['data']);

            $this->assertPaginationResponseStructure($response);

            $response = $this->doAuthenticatedRequest(null, ['page' => 2, 'per_page' => $this->entitiesPerPage]);
            $response->assertStatus(Response::HTTP_OK);
            $this->assertCount(
                $this->entitiesNumber - $this->entitiesPerPage,
                json_decode($response->getContent(), true)['data']
            );
            $this->assertPaginationResponseStructure($response);
        } else {
            $this->assertCount($this->entitiesNumber, $this->getResponseData(
                $this->doAuthenticatedRequest(null, [$this->getUuid()])
            ));
        }
    }

    /**
     * @param TestResponse $response
     */
    protected function assertPaginationResponseStructure(TestResponse $response)
    {
        $response->assertJsonStructure([
            'meta' => [
                'pagination' => [
                    'total' => [],
                ],
            ],
        ]);
        $response->assertJsonFragment(['total' => $this->entitiesNumber]);
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
            $response = $this->doAuthenticatedRequest(null, [$this->getUuid()], $headers);
            $response->assertStatus(Response::HTTP_BAD_REQUEST);
            $this->assertErrorResponseContent(
                $response,
                Response::HTTP_BAD_REQUEST,
                "The '\w+' header must be set to one of the following values '\[.+\]'"
            );

            if ($value) {
                $headers[$header] = '123456789';
                $response = $this->doAuthenticatedRequest(null, [$this->getUuid()], $headers);
                $response->assertStatus(Response::HTTP_BAD_REQUEST);
                $this->assertErrorResponseContent(
                    $response,
                    Response::HTTP_BAD_REQUEST,
                    "The '\w+' header must be set to one of the following values '\[.+\]'"
                );
            }
        }
    }

    /**
     * @param $response
     * @param $expectedCode
     * @param $messagePattern
     */
    protected function assertErrorResponseContent($response, $expectedCode, $messagePattern)
    {
        $this->assertRegExp(
            "/{\"error\":{\"code\":{$expectedCode},\"http_code\":{$expectedCode},\"message\":\"{$messagePattern}\"}}/",
            $response->getContent()
        );
    }

    /**
     * @param $response
     * @param $expectedCode
     * @param $fieldName
     */
    protected function assertValidationResponseContent($response, $expectedCode, $fieldName)
    {
        $formattedFieldName = str_replace('_', ' ', $fieldName);

        $this->assertRegExp(
            "/{\"error\":{\"code\":{$expectedCode},\"http_code\":{$expectedCode},"
            . "\"message\":\"The given data was invalid.\","
            . "\"errors\":{\"{$fieldName}\":\[\"The {$formattedFieldName} field is required.\"\]}}}/",
            $response->getContent()
        );
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
     * @return string
     */
    protected function getAuthorizationBearerKey()
    {
        if ($this->authorizationBearer() == $this->entityClass()) {
            return 'team_id';
        }

        $class = explode('\\', $this->authorizationBearer());

        return strtolower(array_pop($class)) . '_' . $this->identifier;
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
            $entityData['commentable_id'] = Campaign::where($this->identifier, $entityData['campaign_uuid'])->first()->id;
            $entityData['commentable_type'] = $entityData['type'];
            unset($entityData['campaign_uuid']);
            unset($entityData['type']);
        }
        return $entityData;
    }
}
