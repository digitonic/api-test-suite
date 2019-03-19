<?php


namespace Digitonic\ApiTestSuite\Concerns;

use Illuminate\Http\Response;

trait TestsRetrieveAction
{
    /**
     * @return array
     */
    public function requiredFields()
    {
        return [];
    }

    /**
     * @return string
     */
    public function httpAction()
    {
        return 'get';
    }

    /**
     * @return array
     */
    public function statusCodes()
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
    public function shouldPaginate()
    {
        return false;
    }

    /**
     * @return array
     */
    public function requiredHeaders()
    {
        return array_merge($this->defaultHeaders, []);
    }
}
