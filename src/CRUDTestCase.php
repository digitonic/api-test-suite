<?php

namespace Digitonic\ApiTestSuite;

use Digitonic\ApiTestSuite\Concerns\AssertPagination;
use Digitonic\ApiTestSuite\Concerns\AssertsErrorFormat;
use Digitonic\ApiTestSuite\Concerns\AssertsOutput;
use Digitonic\ApiTestSuite\Concerns\DeterminesAssertions;
use Digitonic\ApiTestSuite\Concerns\GeneratesTestData;
use Digitonic\ApiTestSuite\Concerns\InteractsWithApi;
use Digitonic\ApiTestSuite\Contracts\AssertsOutput as AssertsOutputI;
use Digitonic\ApiTestSuite\Contracts\CRUDTestCase as CRUDTestCaseI;
use Digitonic\ApiTestSuite\Contracts\DeterminesAssertions as DeterminesAssertionsI;
use Digitonic\ApiTestSuite\Contracts\InteractsWithApi as InteractsWithApiI;
use Illuminate\Foundation\Testing\TestResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

abstract class CRUDTestCase extends TestCase implements CRUDTestCaseI, AssertsOutputI, InteractsWithApiI, DeterminesAssertionsI
{
    use AssertsOutput, InteractsWithApi, AssertsErrorFormat, AssertPagination, InteractsWithApi,
        DeterminesAssertions, GeneratesTestData;

    // TODO write a read.me with assumptions of the package about app structure

    /**
     * @var \Closure
     */
    public $identifierGenerator;

    public $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->identifierGenerator = config('digitonic.api-test-suite.identifier_faker');
        $this->entities = new Collection();
        $this->user = factory(config('digitonic.api-test-suite.api_user_class'))->state('crud')->create();
    }

    /**
     * @throws \ReflectionException
     */
    public function runBaseApiTestSuite()
    {
        if (in_array($this->httpAction(), ['put', 'get', 'delete'])) {
            $numberOfEntities = $this->isListAction($this->httpAction()) ? $this->entitiesNumber() : 1;
            $this->generateEntities($numberOfEntities);
        }
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

    protected function assertCantUseRouteWithoutAuthenticating()
    {
        if ($this->shouldAssertAuthentication()) {
            Auth::logout();
            /** @var TestResponse $response */
            $response = $this->doRequest([], [$this->getCurrentIdentifier()]);
            $this->assertErrorFormat($response, Response::HTTP_UNAUTHORIZED);
        }
    }

    protected function assertNotFound()
    {
        if ($this->shouldAssertNotFound()) {
            $response = $this->doAuthenticatedRequest($this->payload(), [$this->identifierGenerator->call($this)]);
            $this->assertErrorFormat($response, Response::HTTP_NOT_FOUND);
        }
    }

    protected function assertFailedValidationForRequiredFields()
    {
        if ($this->shouldAssertValidation()) {
            foreach ($this->requiredFields() as $key) {
                if (!isset($this->payload()[$key])) {
                    $this->fail('The field ' . $key . ' is required');
                }
                $this->assertRequiredField($this->payload(), $key, !is_array($this->payload()[$key]));
            }
        }
    }

    /**
     * @param $data
     * @param $key
     * @param bool $assertValidationResponse
     */
    protected function assertRequiredField(array $data, $key, $assertValidationResponse)
    {
        unset($data[$key]);

        /** @var TestResponse $response */
        $response = $this->doAuthenticatedRequest($data, [$this->getCurrentIdentifier()]);
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
        if ($this->shouldAssertForbiddenAction()) {
            $entity = $this->generateEntityNotOwnedByUser();
            /** @var TestResponse $response */
            $response = $this->doRequest([], [$entity[$this->identifier()]]);
            $this->assertErrorFormat($response, Response::HTTP_FORBIDDEN);
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
            $response = $this->doAuthenticatedRequest($this->payload(), [$this->getCurrentIdentifier()], $headers);
            $this->assertErrorFormat($response, Response::HTTP_BAD_REQUEST, []);

            if ($value) {
                $headers[$header] = '123456789';
                $response = $this->doAuthenticatedRequest($this->payload(), [$this->getCurrentIdentifier()], $headers);
                $this->assertErrorFormat($response, Response::HTTP_BAD_REQUEST);
            }
        }
    }

    /**
     * @throws \ReflectionException
     */
    protected function assertCreate()
    {
        if ($this->shouldAssertCreation()) {
            /** @var TestResponse $response */
            $response = $this->doAuthenticatedRequest($this->payload());
            $response->assertStatus(Response::HTTP_CREATED);
            $this->checkTransformerData(
                $this->getResponseData($response),
                $this->identifier(),
                $this->httpAction(),
                $this->includedData()
            );
            $this->assertCreatedOnlyOnce();
        }
    }

    /**
     * @throws \ReflectionException
     */
    protected function assertCreatedOnlyOnce()
    {
        $payload = $this->payload();
        $this->doAuthenticatedRequest($payload);
        $payload = $this->jsonEncodeDataFields($payload);
        foreach ($payload as $key => $item) {
            if (in_array($key, array_keys($this->includedData()))) {
                unset($payload[$key]);
            }
        }

        foreach ($this->manyToManyRelationships() as $attribute) {
            unset($payload[$attribute]);
        }

        $this->modifyCommentable($payload);
        $this->assertCount(1, $this->resourceClass()::where($payload)->get());
    }

    protected function assertUpdate()
    {
        if ($this->shouldAssertUpdate()) {
            $data = $this->generateUpdateData($this->payload());
            foreach (array_keys($this->includedData()) as $included) {
                unset($data[$included]);
            }
            /** @var TestResponse $response */
            $response = $this->doAuthenticatedRequest($data, [$this->getCurrentIdentifier()]);
            $response->assertStatus(Response::HTTP_ACCEPTED);
            $this->checkTransformerData(
                $this->getResponseData($response),
                $this->identifier(),
                $this->httpAction(),
                $this->includedData()
            );
            $this->assertCount(1, $this->resourceClass()::where([
                $this->identifier() => $this->getCurrentIdentifier()
            ])->get());
        }
    }

    protected function assertRetrieve()
    {
        if ($this->shouldAssertRetrieve($this->httpAction())) {
            $response = $this->doAuthenticatedRequest($this->payload(), [$this->getCurrentIdentifier()]);
            $response->assertStatus(Response::HTTP_OK);
            $this->checkTransformerData(
                $this->getResponseData($response),
                $this->identifier(),
                $this->httpAction(),
                $this->includedData()
            );
        }
    }

    protected function assertListAll()
    {
        if ($this->shouldAssertListAll($this->httpAction())) {
            $entitiesNumber = $this->entitiesNumber();
            if ($this->shouldAssertPaginate()) {
                $entitiesPerPage = $this->entitiesPerPage();
                foreach ([1 => $entitiesPerPage, 2 => ($entitiesNumber - $entitiesPerPage)] as $page => $count) {
                    /** @var TestResponse $response */
                    $response = $this->doAuthenticatedRequest($this->payload(), ['page' => $page, 'per_page' => $entitiesPerPage]);
                    $this->assertPaginationFormat($response, $count, $entitiesNumber);
                }
            } else {
                $response = $this->doAuthenticatedRequest($this->payload());
                $this->assertCount($entitiesNumber, $this->getResponseData($response));
            }
            $response->assertStatus(Response::HTTP_OK);
            $this->checkTransformerData(
                $this->getResponseData($response),
                $this->identifier(),
                $this->httpAction(),
                $this->includedData()
            );
        }
    }

    protected function assertDelete()
    {
        if ($this->shouldAssertDeletion()) {
            $response = $this->doAuthenticatedRequest($this->payload(), [$this->getCurrentIdentifier()]);
            $response->assertStatus(Response::HTTP_NO_CONTENT);
            $this->assertEmpty($response->getContent());
            $this->assertNull($this->resourceClass()::find($this->entities->first()->id));
        }
    }
}
