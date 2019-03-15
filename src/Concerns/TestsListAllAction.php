<?php

namespace Digitonic\ApiTestSuite\Concerns;

use Illuminate\Http\Response;

trait TestsListAllAction
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
            Response::HTTP_OK
        ];
    }

    /**
     * @return bool
     */
    protected function shouldPaginate()
    {
        return true;
    }

    /**
     * @return array
     */
    protected function requiredHeaders()
    {
        return array_merge($this->defaultHeaders, []);
    }
}
