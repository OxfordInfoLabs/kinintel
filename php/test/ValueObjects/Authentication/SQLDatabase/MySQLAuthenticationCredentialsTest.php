<?php


namespace Kinintel\ValueObjects\Authentication\SQLDatabase;

use Kinikit\Persistence\Database\Vendors\MySQL\MySQLDatabaseConnection;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class MySQLAuthenticationCredentialsTest extends TestCase {

    public function testCanReturnDatabaseConnectionForConfiguredMySQLConnection() {

        $credentials = new MySQLAuthenticationCredentials("localhost", null, "kininteltest",
            null, "utf8", "kininteltest", "kininteltest");

        $databaseInstance = $credentials->returnDatabaseConnection();
        $this->assertInstanceOf(MySQLDatabaseConnection::class, $databaseInstance);


    }

}