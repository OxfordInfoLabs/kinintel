<?php


namespace Kinintel\Objects\Datasource;


use Kinintel\ValueObjects\Authentication\AuthenticationCredentials;

class TestAuthenticationCredentials implements AuthenticationCredentials {

    /**
     * @var string
     * @required
     */
    private $username;

    /**
     * TestDatasourceConfig constructor.
     * @param string $username
     */
    public function __construct($username = null) {
        $this->username = $username;
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
}