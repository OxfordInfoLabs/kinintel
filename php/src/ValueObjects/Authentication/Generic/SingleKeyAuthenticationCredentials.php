<?php

namespace Kinintel\ValueObjects\Authentication\Generic;

use Kinintel\ValueObjects\Authentication\AuthenticationCredentials;

/**
 * Simple generic single key authentication credentials
 *
 * Class SingleKeyAuthenticationCredentials
 */
class SingleKeyAuthenticationCredentials implements AuthenticationCredentials {

    /**
     * @var string
     */
    private $key;

    /**
     * SingleKeyAuthenticationCredentials constructor.
     *
     * @param string $key
     */
    public function __construct($key) {
        $this->key = $key;
    }

    /**
     * @return string
     */
    public function getKey() {
        return $this->key;
    }

    /**
     * @param string $key
     */
    public function setKey($key) {
        $this->key = $key;
    }


}