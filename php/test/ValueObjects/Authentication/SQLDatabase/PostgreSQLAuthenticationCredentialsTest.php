<?php

namespace Kinintel\Test\ValueObjects\Authentication\SQLDatabase;

use Kinikit\Persistence\Database\Vendors\PostgreSQL\PostgreSQLDatabaseConnection;
use Kinintel\ValueObjects\Authentication\SQLDatabase\PostgreSQLAuthenticationCredentials;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class PostgreSQLAuthenticationCredentialsTest extends TestCase {

    public function testDatabaseConnectionReturnedCorrectlyForPostgreSQL() {

        $authCreds = new PostgreSQLAuthenticationCredentials("localhost", null, "kininteltest", "kininteltest", "kininteltest");
        $database = $authCreds->returnDatabaseConnection();
        $this->assertInstanceOf(PostgreSQLDatabaseConnection::class, $database);

    }

    public function testCanParseFunctionRemappings() {

        $authCreds = new PostgreSQLAuthenticationCredentials("localhost", null, "kininteltest", "kininteltest", "kininteltest");

        $sql = "IFNULL(condition)";
        $result = $authCreds->parseSQL($sql);
        $this->assertEquals("COALESCE(condition)", $result);

        $sql = "GROUP_CONCAT(first,second)";
        $result = $authCreds->parseSQL($sql);
        $this->assertEquals("STRING_AGG(first,second)", $result);

        $sql = "INSTR(a,b)";
        $result = $authCreds->parseSQL($sql);
        $this->assertEquals("POSITION(a IN b)", $result);

        $sql = "EPOCH_SECONDS(test)";
        $result = $authCreds->parseSQL($sql);
        $this->assertEquals("EXTRACT(EPOCH FROM test)", $result);

    }

}