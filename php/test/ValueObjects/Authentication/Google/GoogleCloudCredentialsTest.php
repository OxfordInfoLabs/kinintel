<?php

namespace Kinintel\Test\ValueObjects\Authentication\Google;

use Kinintel\ValueObjects\Authentication\Google\GoogleCloudCredentials;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class GoogleCloudCredentialsTest extends TestCase {

    public function testCanReturnDecryptedCredsWhenEncryptedFlagSet() {

        $creds = new GoogleCloudCredentials(file_get_contents(__DIR__ . "/test-creds.json"), true);

        $jsonString = $creds->getJsonString();

        $this->assertEquals('{"name":"John"}', $jsonString);

    }

}