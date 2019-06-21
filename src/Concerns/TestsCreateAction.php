<?php

namespace Digitonic\ApiTestSuite\Concerns;

use Illuminate\Http\Response;

trait TestsCreateAction
{
    /**
     * @return string
     */
    public function httpAction()
    {
        return 'post';
    }

    /**
     * @return array
     */
    public function statusCodes()
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
    public function shouldAssertPaginate()
    {
        return false;
    }

    /**
     * @return array
     */
    public function requiredHeaders()
    {
        return $this->defaultHeaders();
    }

    public function creationHeaders()
    {
        return $this->defaultHeaders();
    }

    /**
     * @return bool
     */
    public function cannotBeDuplicated()
    {
        return true;
    }
}
