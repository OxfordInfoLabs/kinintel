<?php

namespace Kinintel\ValueObjects\Authentication\WebService;

use Kinikit\Core\HTTP\Request\Headers;
use Kinikit\Core\HTTP\Request\Request;

class BasicAuthenticationCredentials implements WebServiceCredentials {

    /**
     * @var string
     * @required
     */
    private $username;


    /**
     * @var string
     * @required
     */
    private $password;

    /**
     * BasicAuthenticationCredentials constructor.
     *
     * @param string $username
     * @param string $password
     */
    public function __construct($username = null, $password = null) {
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getUsername() {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername($username) {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getPassword() {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword($password) {
        $this->password = $password;
    }


    /**
     * Process the request prior to sending and return a new request
     * with auth applied.
     *
     * @param Request $request
     * @return Request
     */
    public function processRequest($request) {

        $headers = $request->getHeaders();
        $headers->set(Headers::AUTHORISATION, "Basic " . base64_encode($this->username . ":" . $this->password));

        return $request;
    }
}