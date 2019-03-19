<?php

namespace Digitonic\ApiTestSuite\Concerns;

use Illuminate\Http\Response;

trait DeterminesAssertions
{
    /**
     * @param $httpAction
     * @return bool
     */
    protected function isListAction($httpAction)
    {
        return !$this->shouldReturnsStatus(Response::HTTP_NOT_FOUND) && $httpAction == 'get';
    }

    /**
     * @param int $statusCode
     * @return bool
     */
    protected function shouldReturnsStatus($statusCode)
    {
        return collect($this->statusCodes())->contains($statusCode);
    }

    /**
     * @return bool
     */
    protected function shouldAssertAuthentication()
    {
        return $this->shouldReturnsStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @return bool
     */
    protected function shouldAssertNotFound()
    {
        return $this->shouldReturnsStatus(Response::HTTP_NOT_FOUND);
    }

    /**
     * @return bool
     */
    protected function shouldAssertValidation()
    {
        return $this->shouldReturnsStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @return bool
     */
    protected function shouldAssertForbiddenAction()
    {
        return $this->shouldReturnsStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @return bool
     */
    protected function shouldAssertCreation()
    {
        return $this->shouldReturnsStatus(Response::HTTP_CREATED);
    }

    /**
     * @return bool
     */
    protected function shouldAssertUpdate()
    {
        return $this->shouldReturnsStatus(Response::HTTP_ACCEPTED);
    }

    /**
     * @param $httpAction
     * @return bool
     */
    protected function shouldAssertRetrieve($httpAction)
    {
        return $this->shouldReturnsStatus(Response::HTTP_OK) && !$this->isListAction($httpAction);
    }

    /**
     * @param $httpAction
     * @return bool
     */
    protected function shouldAssertListAll($httpAction)
    {
        return $this->shouldReturnsStatus(Response::HTTP_OK) && $this->isListAction($httpAction);
    }

    /**
     * @return bool
     */
    protected function shouldAssertDeletion()
    {
        return $this->shouldReturnsStatus(Response::HTTP_NO_CONTENT);
    }
}
