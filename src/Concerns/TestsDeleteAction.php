<?php

namespace Digitonic\ApiTestSuite\Concerns;

use Illuminate\Http\Response;

trait TestsDeleteAction
{
    /**
     * @return string
     */
    public function httpAction()
    {
        return 'delete';
    }

    /**
     * @return array
     */
    public function statusCodes()
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
    public function requiredFields()
    {
        return [];
    }

    /**
     * @return bool
     */
    public function shouldAssertPaginate()
    {
        return false;
    }

    /**
     * @return array
     */
    public function requiredHeaders()
    {
        return array_merge($this->defaultHeaders(), []);
    }
}
