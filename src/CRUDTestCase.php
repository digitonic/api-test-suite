<?php

namespace Digitonic\ApiTestSuite;

use Digitonic\ApiTestSuite\Concerns\AssertResponsePagination;
use Digitonic\ApiTestSuite\Concerns\AssertsErrorFormat;
use Digitonic\ApiTestSuite\Concerns\AssertsTransformerData;
use Digitonic\ApiTestSuite\Concerns\DeterminesAssertions;
use Digitonic\ApiTestSuite\Concerns\InteractsWithApi;
use Digitonic\ApiTestSuite\Contracts\AssertResponsePagination as AssertResponsePaginationI;
use Digitonic\ApiTestSuite\Contracts\AssertsErrorFormat as AssertsErrorFormatI;
use Digitonic\ApiTestSuite\Contracts\AssertsTransformerData as AssertsTransformerDataI;
use Digitonic\ApiTestSuite\Contracts\CRUDTestCase as CRUDTestCaseI;
use Digitonic\ApiTestSuite\Contracts\DeterminesAssertions as DeterminesApiTestContextI;
use Digitonic\ApiTestSuite\Contracts\InteractsWithApi as InteractsWithApiI;
use Illuminate\Foundation\Testing\TestResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

abstract class CRUDTestCase
    extends TestCase
    implements CRUDTestCaseI, AssertsTransformerDataI, AssertsErrorFormatI, AssertResponsePaginationI, InteractsWithApiI,
    DeterminesApiTestContextI
{
    use AssertsTransformerData, InteractsWithApi, AssertsErrorFormat, AssertResponsePagination, InteractsWithApi,
        DeterminesAssertions;

    // TODO write a read.me with assumptions of the package about app structure

    /**
     * @var \Closure
     */
    public $identifier;

    /**
     * @var \Closure
     */
    public $identifierGenerator;

    /**
     * @var DataGenerator
     */
    public $dataGenerator;

    public function setUp(): void
    {
        parent::setUp();
        $this->identifier = config('digitonic.api-test-suite.identifier_field');
        $this->identifierGenerator = config('digitonic.api-test-suite.identifier_faker');
        $this->dataGenerator = new DataGenerator($this);
    }

    /**
     * @throws \ReflectionException
     */
    public function runBaseApiTestSuite()
    {
        $this->dataGenerator->generateEntities();
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
            $response = $this->doRequest([], [$this->dataGenerator->getIdentifier()]);
            $this->assertErrorFormat($response, Response::HTTP_UNAUTHORIZED);
        }
    }

    protected function assertNotFound()
    {
        if ($this->shouldAssertNotFound()) {
            $response = $this->doAuthenticatedRequest(null, [$this->identifierGenerator->call($this)]);
            $this->assertErrorFormat($response, Response::HTTP_NOT_FOUND);
        }
    }

    protected function assertFailedValidationForRequiredFields()
    {
        if ($this->shouldAssertValidation()) {
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
    protected function assertRequiredField(array $data, $key, $assertValidationResponse)
    {
        unset($data[$key]);

        /** @var TestResponse $response */
        $response = $this->doAuthenticatedRequest($data, [$this->dataGenerator->getIdentifier()]);
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
            $entity = $this->dataGenerator->generateEntityNotOwnedByUser();
            /** @var TestResponse $response */
            $response = $this->doRequest([], [$entity[$this->identifier->call($this)]]);
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
            $response = $this->doAuthenticatedRequest(null, [$this->dataGenerator->getIdentifier()], $headers);
            $this->assertErrorFormat($response, Response::HTTP_BAD_REQUEST, []);

            if ($value) {
                $headers[$header] = '123456789';
                $response = $this->doAuthenticatedRequest(null, [$this->dataGenerator->getIdentifier()], $headers);
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
            $response = $this->doAuthenticatedRequest();
            $response->assertStatus(Response::HTTP_CREATED);
            $this->checkTransformerData(
                $this->getResponseData($response),
                $this->identifier->call($this),
                $this->dataGenerator,
                $this->httpAction()
            );
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

        $this->dataGenerator->modifyCommentable($entityData);
        $this->assertCount(1, $this->entityClass()::where($entityData)->get());
    }

    protected function assertUpdate()
    {
        if ($this->shouldAssertUpdate()) {
            $data = $this->dataGenerator->generateUpdateData($this->entityData());
            foreach (array_keys($this->includedData()) as $included) {
                unset($data[$included]);
            }
            /** @var TestResponse $response */
            $response = $this->doAuthenticatedRequest($data, [$this->dataGenerator->getIdentifier()]);
            $response->assertStatus(Response::HTTP_ACCEPTED);
            $this->checkTransformerData(
                $this->getResponseData($response),
                $this->identifier->call($this),
                $this->dataGenerator,
                $this->httpAction()
            );
            $this->assertCount(1, $this->entityClass()::where([
                $this->identifier->call($this) => $this->dataGenerator->getIdentifier()
            ])->get());
        }
    }

    protected function assertRetrieve()
    {
        if ($this->shouldAssertRetrieve($this->httpAction())) {
            $response = $this->doAuthenticatedRequest(null, [$this->dataGenerator->getIdentifier()]);
            $response->assertStatus(Response::HTTP_OK);
            $this->checkTransformerData(
                $this->getResponseData($response),
                $this->identifier->call($this),
                $this->dataGenerator,
                $this->httpAction()
            );
        }
    }

    protected function assertListAll()
    {
        if ($this->shouldAssertListAll($this->httpAction())) {
            $entitiesNumber = $this->entitiesNumber();
            if ($this->shouldAssertPaginate()) {
                $entitiesPerPage = $this->entitiesPerPage();
                foreach([1 => $entitiesPerPage, 2 => ($entitiesNumber - $entitiesPerPage)] as $page=>$count){
                    /** @var TestResponse $response */
                    $response = $this->doAuthenticatedRequest(null, ['page' => $page, 'per_page' => $entitiesPerPage]);
                    $this->assertPaginationFormat($response, $count, $entitiesNumber);
                }
            } else {
                $response = $this->doAuthenticatedRequest(null);
                $this->assertCount($entitiesNumber, $this->getResponseData($response));
            }
            $response->assertStatus(Response::HTTP_OK);
            $this->checkTransformerData(
                $this->getResponseData($response),
                $this->identifier->call($this),
                $this->dataGenerator,
                $this->httpAction()
            );
        }
    }

    protected function assertDelete()
    {
        if ($this->shouldAssertDeletion()) {
            $response = $this->doAuthenticatedRequest(null, [$this->dataGenerator->getIdentifier()]);
            $response->assertStatus(Response::HTTP_NO_CONTENT);
            $this->assertEmpty($response->getContent());
            $this->assertNull($this->entityClass()::find($this->dataGenerator->entities->first()->id));
        }
    }
}
