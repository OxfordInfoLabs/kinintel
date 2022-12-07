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

}