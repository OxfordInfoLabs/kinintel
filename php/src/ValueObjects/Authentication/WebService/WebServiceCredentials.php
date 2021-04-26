<?php


namespace Kinintel\ValueObjects\Authentication\WebService;


use Kinikit\Core\HTTP\Request\Request;
use Kinintel\ValueObjects\Authentication\AuthenticationCredentials;

interface WebServiceCredentials extends AuthenticationCredentials {

    /**
     * Process request for these credentials and return a new request to be sent off via the dispatcher
     *
     * @param Request $request
     * @return Request
     */
    public function processRequest($request);

}