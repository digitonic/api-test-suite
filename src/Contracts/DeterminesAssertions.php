<?php

namespace Digitonic\ApiTestSuite\Contracts;

interface DeterminesAssertions
{
    /**
     * @param int $statusCode
     * @return bool
     */
    public function shouldReturnsStatus($statusCode);

    /**
     * @return bool
     */
    public function shouldAssertAuthentication();

    /**
     * @return bool
     */
    public function shouldAssertNotFound();

    /**
     * @return bool
     */
    public function shouldAssertValidation();

    /**
     * @return bool
     */
    public function shouldAssertForbiddenAction();

    /**
     * @return bool
     */
    public function shouldAssertCreation();

    /**
     * @return bool
     */
    public function shouldAssertUpdate();

    /**
     * @param $httpAction
     * @return bool
     */
    public function shouldAssertRetrieve($httpAction);

    /**
     * @param $httpAction
     * @return bool
     */
    public function shouldAssertListAll($httpAction);

    /**
     * @return bool
     */
    public function shouldAssertDeletion();

    /**
     * @return bool
     */
    public function shouldAssertPaginate();

    /**
     * @return array
     */
    public function statusCodes();
 }
