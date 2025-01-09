<?php


namespace Kinintel\ValueObjects\Authentication\Google;


use Kinintel\ValueObjects\Authentication\AuthenticationCredentials;

class GoogleCloudCredentials implements AuthenticationCredentials {

    /**
     * @var string
     */
    private string $jsonString;

    /**
     * @var bool
     */
    private bool $encrypted;

    /**
     * GoogleCloudCredentials constructor.
     *
     * @param string $jsonString
     */
    public function __construct(string $jsonString, bool $encrypted = false) {
        $this->jsonString = $jsonString;
        $this->encrypted = $encrypted;
    }

    /**
     * @return string
     */
    public function getJsonString(): string {

        if ($this->encrypted) {
            return base64_decode($this->jsonString);
        } else {
            return $this->jsonString;
        }
    }

    /**
     * @param string $jsonString
     */
    public function setJsonString(string $jsonString): void {
        $this->jsonString = $jsonString;
    }

    public function isEncrypted(): bool {
        return $this->encrypted;
    }

    public function setEncrypted(bool $encrypted): void {
        $this->encrypted = $encrypted;
    }

}