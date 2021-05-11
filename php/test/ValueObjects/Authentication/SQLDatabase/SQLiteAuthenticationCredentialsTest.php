<?php


namespace Kinintel\Test\ValueObjects\Authentication\SQLDatabase;

use Kinikit\Persistence\Database\Vendors\SQLite3\SQLite3DatabaseConnection;
use Kinintel\ValueObjects\Authentication\SQLDatabase\SQLiteAuthenticationCredentials;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class SQLiteAuthenticationCredentialsTest extends TestCase {

    public function testDatabaseConnectionReturnedCorrectlyForSQLite() {

        $authCreds = new SQLiteAuthenticationCredentials(__DIR__ . "/test.db");
        $database = $authCreds->returnDatabaseConnection();
        $this->assertInstanceOf(SQLite3DatabaseConnection::class, $database);

    }

}