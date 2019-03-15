<?php


namespace Digitonic\ApiTestSuite\Concerns;

use Illuminate\Http\Response;

trait TestsUpdateAction
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
        return 'put';
    }

    /**
     * @return array
     */
    protected function statusCodes()
    {
        return [
            Response::HTTP_ACCEPTED,
            Response::HTTP_UNAUTHORIZED,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            Response::HTTP_NOT_FOUND,
            Response::HTTP_FORBIDDEN
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
