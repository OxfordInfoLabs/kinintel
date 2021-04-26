<?php


namespace Kinintel\ValueObjects\Authentication\WebService;


use Kinikit\Core\HTTP\Request\Request;

class QueryParameterAuthenticationCredentials implements WebServiceCredentials {

    /**
     * @var string[]
     * @required
     */
    private $authParams;

    /**
     * QueryParameterAuthenticationCredentials constructor.
     *
     * @param string[] $authParams
     */
    public function __construct($authParams = []) {
        $this->authParams = $authParams;
    }

    /**
     * @return string[]
     */
    public function getAuthParams() {
        return $this->authParams;
    }

    /**
     * @param string[] $authParams
     */
    public function setAuthParams($authParams) {
        $this->authParams = $authParams;
    }


    /**
     * Process the request injecting auth
     *
     * @param Request $request
     * @return Request
     */
    public function processRequest($request) {

        // Augment params with our params
        $params = $this->authParams ?? [];
        $request->setParameters(array_merge($request->getParameters(), $params));

        return $request;

    }
}