<?php


namespace Kinintel\Objects\Datasource;

use Kinintel\Exception\InvalidDatasourceAuthenticationCredentialsException;
use Kinintel\Exception\InvalidDatasourceConfigException;
use Kinintel\Exception\InvalidDatasourceTypeException;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\Objects\Datasource\WebService\JSONWebServiceDatasource;
use Kinintel\Objects\Datasource\WebService\WebServiceDatasource;
use Kinintel\ValueObjects\Authentication\SQLDatabase\MySQLAuthenticationCredentials;
use Kinintel\ValueObjects\Authentication\WebService\BasicAuthenticationCredentials;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Datasource\Configuration\WebService\WebserviceDataSourceConfig;
use Kinintel\ValueObjects\Datasource\DatasourceInstanceInfo;
use Kinintel\ValueObjects\Datasource\DatasourceUpdateConfig;
use Kinintel\ValueObjects\Datasource\WebService\JSONWebServiceDataSourceConfig;

include_once "autoloader.php";

class DatasourceInstanceTest extends \PHPUnit\Framework\TestCase {


    public function testInvalidDatasourcesThrowValidationExceptions() {


        $dataSourceInstance = new DatasourceInstance("badsource", "Bad Datasource", "wrong");

        try {
            $dataSourceInstance->returnDataSource();
            $this->fail("Should have thrown here");
        } catch (InvalidDatasourceTypeException $e) {
            $this->assertTrue(true);
        }


        // Missing credentials key
        $dataSourceInstance = new DatasourceInstance("badcredentials", "Bad Credentials", "webservice",
            ["url" => "https://hello.com"], "badcreds");

        try {
            $dataSourceInstance->returnDataSource();
            $this->fail("Should have thrown here");
        } catch (InvalidDatasourceAuthenticationCredentialsException $e) {
            $this->assertTrue(true);
        }


        // Wrong credentials type
        $dataSourceInstance = new DatasourceInstance("badcredentials", "Bad Credentials", "webservice",
            ["url" => "https://hello.com"], null, "badtype");

        try {
            $dataSourceInstance->returnDataSource();
            $this->fail("Should have thrown here");
        } catch (InvalidDatasourceAuthenticationCredentialsException $e) {
            $this->assertTrue(true);
        }

        // Invalid credentials
        $dataSourceInstance = new DatasourceInstance("badcredentials", "Bad Credentials", "webservice",
            ["url" => "https://hello.com"], null, "http-basic", [
                "username" => "Bob"
            ]);

        try {
            $dataSourceInstance->returnDataSource();
            $this->fail("Should have thrown here");
        } catch (InvalidDatasourceAuthenticationCredentialsException $e) {
            $this->assertTrue(true);
        }


        // Invalid config
        $dataSourceInstance = new DatasourceInstance("badconfig", "Bad Credentials", "webservice", []);
        try {
            $dataSourceInstance->returnDataSource();
            $this->fail("Should have thrown here");
        } catch (InvalidDatasourceConfigException $e) {
            $this->assertTrue(true);
        }


    }


    public function testValidDatasourceInstanceReturnsDataSourceCorrectly() {

        // Valid credentials
        $dataSourceInstance = new DatasourceInstance("goodone", "Good One", "webservice",
            ["url" => "https://hello.com"], null, "http-basic", [
                "username" => "Bob",
                "password" => "password"
            ]);


        $dataSource = $dataSourceInstance->returnDataSource();
        $this->assertEquals(new WebServiceDatasource(new WebserviceDataSourceConfig("https://hello.com"),
            new BasicAuthenticationCredentials("Bob", "password"), null, new DatasourceInstanceInfo(new DatasourceInstance("goodone", "Good One", "webservice"))), $dataSource);

    }


    public function testValidUpdatableDatasourceInstanceReturnsDatasourceWithUpdateConfigIntact() {

        $dataSourceInstance = new DatasourceInstance("updatable", "Updatable DS", "sqldatabase",
            [
                "source" => "table",
                "tableName" => "test_one"
            ], null, "mysql", [
                "host" => "localhost",
                "database" => "test",
                "username" => "test",
                "password" => "test"
            ], [
                "keyFieldNames" => [
                    "name", "dob"
                ]
            ]);

        $dataSource = $dataSourceInstance->returnDataSource();
        $this->assertEquals(new SQLDatabaseDatasource(new SQLDatabaseDatasourceConfig("table", "test_one"),
            new MySQLAuthenticationCredentials("localhost", null, "test", null, null, "test", "test"),
            new DatasourceUpdateConfig(["name", "dob"]), null, null, new DatasourceInstanceInfo(new DatasourceInstance("updatable", "Updatable DS", "test"))), $dataSource);

    }

}