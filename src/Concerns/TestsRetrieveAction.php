<?php


namespace Digitonic\ApiTestSuite\Concerns;

use Illuminate\Http\Response;

trait TestsRetrieveAction
{
    /**
     * @return array
     */
    protected function requiredFields()
    {
        return [];
    }

    /**
     * @return string
     */
    protected function httpAction()
    {
        return 'get';
    }

    /**
     * @return array
     */
    protected function statusCodes()
    {
        return [
            Response::HTTP_UNAUTHORIZED,
            Response::HTTP_FORBIDDEN,
            Response::HTTP_NOT_FOUND,
            Response::HTTP_OK
        ];
    }

    /**
     * @return bool
     */
    protected function shouldPaginate()
    {
        return false;
    }

    /**
     * @return array
     */
    protected function requiredHeaders()
    {
        return array_merge($this->defaultHeaders, []);
    }
}
