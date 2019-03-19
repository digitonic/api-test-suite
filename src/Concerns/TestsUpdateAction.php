<?php


namespace Digitonic\ApiTestSuite\Concerns;

use Illuminate\Http\Response;

trait TestsUpdateAction
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
        return 'put';
    }

    /**
     * @return array
     */
    public function statusCodes()
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
