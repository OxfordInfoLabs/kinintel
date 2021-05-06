<?php


namespace Kinintel\ValueObjects\Authentication\Generic;


use Kinintel\ValueObjects\Authentication\AuthenticationCredentials;

class AccessKeyAndSecretAuthenticationCredentials implements AuthenticationCredentials {

    /**
     * @var string
     */
    private $accessKey;

    /**
     * @var string
     */
    private $secret;

    /**
     * SingleKeyAuthenticationCredentials constructor.
     *
     * @param string $accessKey
     * @param string $secret
     */
    public function __construct($accessKey, $secret) {
        $this->accessKey = $accessKey;
        $this->secret = $secret;
    }

    /**
     * @return string
     */
    public function getAccessKey() {
        return $this->accessKey;
    }

    /**
     * @param string $accessKey
     */
    public function setAccessKey($accessKey) {
        $this->accessKey = $accessKey;
    }

    /**
     * @return string
     */
    public function getSecret() {
        return $this->secret;
    }

    /**
     * @param string $secret
     */
    public function setSecret($secret) {
        $this->secret = $secret;
    }


}