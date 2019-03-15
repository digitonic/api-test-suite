<?php

namespace Digitonic\ApiTestSuite\Concerns;

use Illuminate\Http\Response;

trait TestsCreateAction
{
    /**
     * @return string
     */
    protected function httpAction()
    {
        return 'post';
    }

    /**
     * @return array
     */
    protected function statusCodes()
    {
        return [
            Response::HTTP_CREATED,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            Response::HTTP_UNAUTHORIZED,
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
