<?php

namespace Digitonic\ApiTestSuite\Concerns;

use Illuminate\Http\Response;

trait DeterminesAssertions
{
    /**
     * @param $httpAction
     * @return bool
     */
    public function isListAction($httpAction)
    {
        return !$this->shouldReturnsStatus(Response::HTTP_NOT_FOUND) && $httpAction == 'get';
    }

    /**
     * @param int $statusCode
     * @return bool
     */
    public function shouldReturnsStatus($statusCode)
    {
        return collect($this->statusCodes())->contains($statusCode);
    }

    /**
     * @return bool
     */
    public function shouldAssertAuthentication()
    {
        return $this->shouldReturnsStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @return bool
     */
    public function shouldAssertNotFound()
    {
        return $this->shouldReturnsStatus(Response::HTTP_NOT_FOUND);
    }

    /**
     * @return bool
     */
    public function shouldAssertValidation()
    {
        return $this->shouldReturnsStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @return bool
     */
    public function shouldAssertForbiddenAction()
    {
        return $this->shouldReturnsStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @return bool
     */
    public function shouldAssertCreation()
    {
        return $this->shouldReturnsStatus(Response::HTTP_CREATED);
    }

    /**
     * @return bool
     */
    public function shouldAssertUpdate()
    {
        return $this->shouldReturnsStatus(Response::HTTP_ACCEPTED);
    }

    /**
     * @param $httpAction
     * @return bool
     */
    public function shouldAssertRetrieve($httpAction)
    {
        return $this->shouldReturnsStatus(Response::HTTP_OK) && !$this->isListAction($httpAction);
    }

    /**
     * @param $httpAction
     * @return bool
     */
    public function shouldAssertListAll($httpAction)
    {
        return $this->shouldReturnsStatus(Response::HTTP_OK) && $this->isListAction($httpAction);
    }

    /**
     * @return bool
     */
    public function shouldAssertDeletion()
    {
        return $this->shouldReturnsStatus(Response::HTTP_NO_CONTENT);
    }
}
