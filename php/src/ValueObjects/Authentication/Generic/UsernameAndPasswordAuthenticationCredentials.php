<?php


namespace Kinintel\ValueObjects\Authentication\Generic;


use Kinintel\ValueObjects\Authentication\AuthenticationCredentials;

class UsernameAndPasswordAuthenticationCredentials implements AuthenticationCredentials {

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * SingleKeyAuthenticationCredentials constructor.
     *
     * @param string $username
     * @param string $password
     */
    public function __construct($username, $password) {
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


}