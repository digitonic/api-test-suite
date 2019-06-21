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
use Digitonic\ApiTestSuite\Contracts\GeneratesTestData as GeneratesTestDataI;
use Digitonic\ApiTestSuite\Contracts\InteractsWithApi as InteractsWithApiI;
use Illuminate\Foundation\Testing\TestResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Tests\TestCase;

abstract class CRUDTestCase extends TestCase implements CRUDTestCaseI, AssertsOutputI, InteractsWithApiI, DeterminesAssertionsI, GeneratesTestDataI
{
    use AssertsOutput, InteractsWithApi, AssertsErrorFormat, AssertPagination, InteractsWithApi,
        DeterminesAssertions, GeneratesTestData;

    // TODO write a read.me with assumptions of the package about app structure

    /**
     * @var \Closure
     */
    public $identifierGenerator;

    public $otherUser;

    public function setUp(): void
    {
        parent::setUp();
        $this->identifierGenerator = config('digitonic.api-test-suite.identifier_faker');
        $this->entities = new Collection();
        $this->user = factory(config('digitonic.api-test-suite.api_user_class'))->state('crud')->create();
        $this->otherUser = factory(config('digitonic.api-test-suite.api_user_class'))->state('crud')->create();
    }

    /**
     * @throws \ReflectionException
     */
    public function runBaseApiTestSuite()
    {
        $this->assertCantUseRouteWithoutAuthenticating();
        $numberOfEntities = $this->isListAction($this->httpAction()) ? $this->entitiesNumber() : 1;
        $this->generateEntities($numberOfEntities, $this->httpAction(), $this->user, $this->otherUser);
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
            /** @var TestResponse $response */
            $response = $this->doRequest([], [$this->identifierGenerator->call($this)]);
            $this->assertErrorFormat($response, Response::HTTP_UNAUTHORIZED);
        }
    }

    protected function assertNotFound()
    {
        if ($this->shouldAssertNotFound()) {
            $response = $this->doAuthenticatedRequest([], [$this->identifierGenerator->call($this)]);
            $this->assertErrorFormat($response, Response::HTTP_NOT_FOUND);
        }
    }

    protected function assertFailedValidationForRequiredFields()
    {
        if ($this->shouldAssertValidation()) {
            foreach ($this->requiredFields() as $key) {
                if (!isset($this->payload[$key])) {
                    $this->fail('The field ' . $key . ' is required');
                }
                $this->assertRequiredField($key, !is_array($this->payload[$key]));
            }
        }
    }

    /**
     * @param $key
     * @param bool $assertValidationResponse
     */
    protected function assertRequiredField($key, $assertValidationResponse)
    {
        $data = $this->payload;
        unset($data[$key]);

        /** @var TestResponse $response */
        $response = $this->doAuthenticatedRequest($data, [$this->getCurrentIdentifier()]);
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        if ($assertValidationResponse) {
            $this->assertErrorFormat(
                $response,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                [
                    'fieldName' => $key,
                    'formattedFieldName' => str_replace('_', ' ', $key)
                ]
            );
        }
    }

    protected function assertAccessIsForbidden()
    {
        if ($this->shouldAssertForbiddenAction()) {
            $entity = $this->generateSingleEntity(
                factory(config('digitonic.api-test-suite.api_user_class'))->state('crud')->create()
            );
            /** @var TestResponse $response */
            $identifier = $this->identifier();
            $response = $this->doAuthenticatedRequest([], [$entity->$identifier]);
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
            $response = $this->doAuthenticatedRequest([], [$this->getCurrentIdentifier()], $headers);
            $this->assertErrorFormat($response, Response::HTTP_BAD_REQUEST, []);

            if ($value) {
                $headers[$header] = '123456789';
                $response = $this->doAuthenticatedRequest([], [$this->getCurrentIdentifier()], $headers);
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
            $response = $this->doAuthenticatedRequest($this->payload, [$this->getCurrentIdentifier()]);
            $response->assertStatus(Response::HTTP_CREATED);
            $this->checkTransformerData(
                $this->getResponseData($response),
                $this->identifier()
            );
            $this->assertCreatedOnlyOnce();
        }
    }

    /**
     * @throws \ReflectionException
     */
    protected function assertCreatedOnlyOnce()
    {
        $this->doAuthenticatedRequest($this->payload, [$this->getCurrentIdentifier()]);
        if ($this->cannotBeDuplicated()) {
            $this->assertCount(1, $this->resourceClass()::all());
        } else {
            $this->assertCount(2, $this->resourceClass()::all());
        }
    }

    protected function assertUpdate()
    {
        if ($this->shouldAssertUpdate()) {
            $data = $this->updateData = $this->generateUpdateData($this->payload, $this->user);
            /** @var TestResponse $response */
            $response = $this->doAuthenticatedRequest($data, [$this->getCurrentIdentifier()]);
            $response->assertStatus(Response::HTTP_ACCEPTED);
            $this->checkTransformerData(
                $this->getResponseData($response),
                $this->identifier()
            );
            $this->assertCount(
                1,
                $this->resourceClass()::where(
                    [
                        $this->identifier() => $this->getCurrentIdentifier()
                    ]
                )->get()
            );
        }
    }

    protected function assertRetrieve()
    {
        if ($this->shouldAssertRetrieve($this->httpAction())) {
            $response = $this->doAuthenticatedRequest([], [$this->getCurrentIdentifier()]);
            $response->assertStatus(Response::HTTP_OK);
            $this->checkTransformerData(
                $this->getResponseData($response),
                $this->identifier()
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
                    $response = $this->doAuthenticatedRequest([], ['page' => $page, 'per_page' => $entitiesPerPage]);
                    $this->assertPaginationFormat($response, $count, $entitiesNumber);
                    $response->assertStatus(Response::HTTP_OK);
                    $this->checkTransformerData(
                        $this->getResponseData($response),
                        $this->identifier()
                    );
                }
            } else {
                $response = $this->doAuthenticatedRequest([]);
                $this->assertCount($entitiesNumber, $this->getResponseData($response));
                $response->assertStatus(Response::HTTP_OK);
                $this->checkTransformerData(
                    $this->getResponseData($response),
                    $this->identifier()
                );
            }
        }
    }

    protected function assertDelete()
    {
        if ($this->shouldAssertDeletion()) {
            $response = $this->doAuthenticatedRequest([], [$this->getCurrentIdentifier()]);
            $response->assertStatus(Response::HTTP_NO_CONTENT);
            $this->assertEmpty($response->getContent());
            $this->assertNull($this->resourceClass()::find($this->entities->first()->id));
        }
    }
}
