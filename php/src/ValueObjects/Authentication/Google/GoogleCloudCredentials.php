<?php


namespace Kinintel\ValueObjects\Authentication\Google;


use Kinintel\ValueObjects\Authentication\AuthenticationCredentials;

class GoogleCloudCredentials implements AuthenticationCredentials {

    /**
     * @var string
     */
    private $jsonString;

    /**
     * GoogleCloudCredentials constructor.
     *
     * @param string $jsonString
     */
    public function __construct($jsonString) {
        $this->jsonString = $jsonString;
    }

    /**
     * @return string
     */
    public function getJsonString() {
        return $this->jsonString;
    }

    /**
     * @param string $jsonString
     */
    public function setJsonString($jsonString) {
        $this->jsonString = $jsonString;
    }


}