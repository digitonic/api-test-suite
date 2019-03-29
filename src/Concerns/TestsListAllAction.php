<?php

namespace Digitonic\ApiTestSuite\Concerns;

use Illuminate\Http\Response;

trait TestsListAllAction
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
            Response::HTTP_OK
        ];
    }

    /**
     * @return bool
     */
    public function shouldAssertPaginate()
    {
        return true;
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
    public function shouldAssertNonDuplication()
    {
        return false;
    }
}
