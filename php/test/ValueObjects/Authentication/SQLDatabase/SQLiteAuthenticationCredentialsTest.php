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

    public function testCanParseFunctionRemappings() {

        $authCreds = new SQLiteAuthenticationCredentials(__DIR__ . "/test.db");

        $sql = "EPOCH_SECONDS(test)";
        $sql = $authCreds->parseSQL($sql);
        $this->assertEquals("STRFTIME('%s',test)", $sql);


        // Check aggregate totals and percentages
        $sql = "COUNT_TOTAL(test)";
        $this->assertEquals("SUM(COUNT(test)) OVER ()", $authCreds->parseSQL($sql));

        $sql = "SUM_TOTAL(test)";
        $this->assertEquals("SUM(SUM(test)) OVER ()", $authCreds->parseSQL($sql));

        $sql = "COUNT_PERCENT(test)";
        $this->assertEquals("100 * COUNT(test) / SUM(COUNT(test)) OVER ()", $authCreds->parseSQL($sql));

        $sql = "SUM_PERCENT(test)";
        $this->assertEquals("100 * SUM(test) / SUM(SUM(test)) OVER ()", $authCreds->parseSQL($sql));

        $sql = "ROW_NUMBER()";
        $this->assertEquals("ROW_NUMBER() OVER (ORDER BY 1=1)", $authCreds->parseSQL($sql));

        $sql = "TOTAL(test)";
        $this->assertEquals("SUM(test) OVER (PARTITION BY null)", $authCreds->parseSQL($sql));

        $sql = "TOTAL(test, test2, test3)";
        $this->assertEquals("SUM(test) OVER (PARTITION BY test2, test3)", $authCreds->parseSQL($sql));

        $sql = "PERCENT(test)";
        $this->assertEquals("100 * test / SUM(test) OVER (PARTITION BY null)", $authCreds->parseSQL($sql));

        $sql = "PERCENT(test, test2, test3)";
        $this->assertEquals("100 * test / SUM(test) OVER (PARTITION BY test2, test3)", $authCreds->parseSQL($sql));

        $sql = "ROW_COUNT()";
        $this->assertEquals("COUNT(*) OVER (PARTITION BY null)", $authCreds->parseSQL($sql));

        $sql = "ROW_COUNT(test, test2)";
        $this->assertEquals("COUNT(*) OVER (PARTITION BY test, test2)", $authCreds->parseSQL($sql));


        $sql = "a RLIKE b";
        $this->assertEquals("a REGEXP b", $authCreds->parseSQL($sql));

    }

}