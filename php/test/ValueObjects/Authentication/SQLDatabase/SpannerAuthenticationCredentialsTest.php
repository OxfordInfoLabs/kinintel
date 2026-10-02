<?php

namespace Kinintel\Test\ValueObjects\Authentication\SQLDatabase;

use Kinintel\ValueObjects\Authentication\SQLDatabase\SpannerAuthenticationCredentials;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class SpannerAuthenticationCredentialsTest extends TestCase {

    public function testCanParseFunctionRemappings() {

        $authCreds = new SpannerAuthenticationCredentials("my-instance", "my-database");

        $sql = "GROUP_CONCAT(statement)";
        $result = $authCreds->parseSQL($sql);
        $this->assertEquals("STRING_AGG(statement)", $result);

    }

}