<?php

namespace Digitonic\ApiTestSuite\Concerns;

use Illuminate\Http\Response;

trait TestsDeleteAction
{
    /**
     * @return string
     */
    protected function httpAction()
    {
        return 'delete';
    }

    /**
     * @return array
     */
    protected function statusCodes()
    {
        return [
            Response::HTTP_NO_CONTENT,
            Response::HTTP_UNAUTHORIZED,
            Response::HTTP_NOT_FOUND,
            Response::HTTP_FORBIDDEN
        ];
    }

    /**
     * @return array
     */
    protected function requiredFields()
    {
        return [];
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
