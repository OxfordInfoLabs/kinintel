<?php


namespace Kinintel\ValueObjects\Authentication\SQLDatabase;

use Kinikit\Persistence\Database\Vendors\MySQL\MySQLDatabaseConnection;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class MySQLAuthenticationCredentialsTest extends TestCase {

    public function testCanReturnDatabaseConnectionForConfiguredMySQLConnection() {

        $credentials = new MySQLAuthenticationCredentials("127.0.0.1", null, "kininteltest",
            3310, "utf8", "kininteltest", "kininteltest");

        $databaseInstance = $credentials->returnDatabaseConnection();
        $this->assertInstanceOf(MySQLDatabaseConnection::class, $databaseInstance);


    }

    public function testCanParseFunctionRemappings() {

        $authCreds = new MySQLAuthenticationCredentials("127.0.0.1", null, "kininteltest",
            3310, "utf8", "kininteltest", "kininteltest");

//        $authCreds->execute("DROP TABLE IF EXISTS test_create");
//
//        $script = "
//            CREATE TABLE test_create (
//                id INTEGER PRIMARY KEY AUTOINCREMENT,
//                number INTEGER,
//                value VARCHAR,
//                last_modifed DATETIME
//            ) ;
//        ";
//
//
//        $authCreds->executeScript($script);
//
//        $metaData = $authCreds->getTableMetaData("test_create");
//        $this->assertEquals(4, sizeof($metaData->getColumns()));
//        $this->assertEquals(1, sizeof($metaData->getPrimaryKeyColumns()));


        $sql = "SELECT GROUP_CONCAT(field) FROM test";
        $this->assertEquals("SELECT GROUP_CONCAT(field SEPARATOR ',') FROM test", $authCreds->parseSQL($sql));

        // Check separator syntax left intact
        $sql = "SELECT GROUP_CONCAT(field SEPARATOR ';') FROM test";
        $this->assertEquals("SELECT GROUP_CONCAT(field SEPARATOR ';') FROM test", $authCreds->parseSQL($sql));

        // Check SQLIte variant mapped correctly
        $sql = "SELECT GROUP_CONCAT(field,';') FROM test";
        $this->assertEquals("SELECT GROUP_CONCAT(field SEPARATOR ';') FROM test", $authCreds->parseSQL($sql));

        $sql = "SELECT group_concat(field,';') FROM test";
        $this->assertEquals("SELECT GROUP_CONCAT(field SEPARATOR ';') FROM test", $authCreds->parseSQL($sql));

        $sql = "EPOCH_SECONDS(test)";
        $this->assertEquals("UNIX_TIMESTAMP(test)", $authCreds->parseSQL($sql));

        $sql = "SELECT *, (EPOCH_SECONDS(first))-(EPOCH_SECONDS(second))  derived1, (EPOCH_SECONDS(`third`))-(EPOCH_SECONDS(`fourth`)) derived2 FROM test LIMIT ? OFFSET ?";
        $expected = "SELECT *, (UNIX_TIMESTAMP(first))-(UNIX_TIMESTAMP(second))  derived1, (UNIX_TIMESTAMP(`third`))-(UNIX_TIMESTAMP(`fourth`)) derived2 FROM test LIMIT ? OFFSET ?";
        $this->assertEquals($expected, $authCreds->parseSQL($sql));


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
        $this->assertEquals("ROW_NUMBER() OVER (PARTITION BY 1=1, 1=1 ORDER BY 1=1, 1=1)", $authCreds->parseSQL($sql));

        $sql = "ROW_NUMBER(test,test2)";
        $this->assertEquals("ROW_NUMBER() OVER (PARTITION BY 1=1, 1=1 ORDER BY test, test2)", $authCreds->parseSQL($sql));

        $sql = "ROW_NUMBER(test,test2, test3, test4)";
        $this->assertEquals("ROW_NUMBER() OVER (PARTITION BY test3, test4 ORDER BY test, test2)", $authCreds->parseSQL($sql));

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

        $sql = "IP_ADDRESS_TO_NUMBER(test)";
        $this->assertEquals("CASE WHEN test LIKE '%:%' THEN (CAST(CONV(SUBSTR(HEX(INET6_ATON(test)), 1, 16), 16, 10) as DECIMAL(65))*18446744073709551616 + CAST(CONV(SUBSTR(HEX(INET6_ATON(test)), 17, 16), 16, 10) as DECIMAL(65))) ELSE INET_ATON(test) END", $authCreds->parseSQL($sql));

        $sql = "IP_NUMBER_TO_ADDRESS(test)";
        $this->assertEquals("CASE WHEN test LIKE '%:%' THEN NULL ELSE INET_NTOA(test) END", $authCreds->parseSQL($sql));

        $sql = "MAXIMUM(test, col1, col2)";
        $this->assertEquals("MAX(test) OVER (PARTITION BY col1, col2)", $authCreds->parseSQL($sql));

        $sql = "MINIMUM(test, col1, col2)";
        $this->assertEquals("MIN(test) OVER (PARTITION BY col1, col2)", $authCreds->parseSQL($sql));

        $sql = "AVERAGE(test, col1, col2)";
        $this->assertEquals("AVG(test) OVER (PARTITION BY col1, col2)", $authCreds->parseSQL($sql));

        $sql = "IIF([[a]] > 0, 'Yes', 'No')";
        $this->assertEquals("IF([[a]] > 0, 'Yes', 'No')", $authCreds->parseSQL($sql));

    }

}