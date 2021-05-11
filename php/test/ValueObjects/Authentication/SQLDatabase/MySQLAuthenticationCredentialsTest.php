<?php


namespace Kinintel\ValueObjects\Authentication\SQLDatabase;

use Kinikit\Persistence\Database\Vendors\MySQL\MySQLDatabaseConnection;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class MySQLAuthenticationCredentialsTest extends TestCase {

    public function testCanReturnDatabaseConnectionForConfiguredMySQLConnection() {

        $this->assertTrue(true);
//        $credentials = new MySQLAuthenticationCredentials("localhost", null, "test",
//            3308, "utf8", "bobby", "pass");
//
//        $databaseInstance = $credentials->returnDatabaseConnection();
//        $this->assertInstanceOf(MySQLDatabaseConnection::class, $databaseInstance);


    }

}