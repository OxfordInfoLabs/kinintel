<?php


namespace Kinintel\ValueObjects\Authentication\WebService;


use Kinikit\Core\HTTP\Request\Request;

class HTTPHeaderAuthenticationCredentials {

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

        foreach ($this->getAuthParams() as $key => $value) {
            $request->getHeaders()->set($key, $value);
        }

        return $request;
    }

}