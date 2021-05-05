<?php


namespace Kinintel\Objects\Datasource;

use Kinintel\Exception\InvalidDatasourceAuthenticationCredentialsException;
use Kinintel\Exception\InvalidDatasourceConfigException;
use Kinintel\Exception\InvalidDatasourceTypeException;
use Kinintel\Objects\Datasource\WebService\JSONWebServiceDatasource;
use Kinintel\ValueObjects\Authentication\WebService\BasicAuthenticationCredentials;
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
        $dataSourceInstance = new DatasourceInstance("badcredentials", "Bad Credentials", "json",
            ["url" => "https://hello.com"], "badcreds");

        try {
            $dataSourceInstance->returnDataSource();
            $this->fail("Should have thrown here");
        } catch (InvalidDatasourceAuthenticationCredentialsException $e) {
            $this->assertTrue(true);
        }


        // Wrong credentials type
        $dataSourceInstance = new DatasourceInstance("badcredentials", "Bad Credentials", "json",
            ["url" => "https://hello.com"], null, "badtype");

        try {
            $dataSourceInstance->returnDataSource();
            $this->fail("Should have thrown here");
        } catch (InvalidDatasourceAuthenticationCredentialsException $e) {
            $this->assertTrue(true);
        }

        // Invalid credentials
        $dataSourceInstance = new DatasourceInstance("badcredentials", "Bad Credentials", "json",
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
        $dataSourceInstance = new DatasourceInstance("badconfig", "Bad Credentials", "json", []);
        try {
            $dataSourceInstance->returnDataSource();
            $this->fail("Should have thrown here");
        } catch (InvalidDatasourceConfigException $e) {
            $this->assertTrue(true);
        }

    }


    public function testValidDatasourceInstanceReturnsDataSourceCorrectly() {

        // Invalid credentials
        $dataSourceInstance = new DatasourceInstance("goodone", "Good One", "json",
            ["url" => "https://hello.com"], null, "http-basic", [
                "username" => "Bob",
                "password" => "password"
            ]);


        $dataSource = $dataSourceInstance->returnDataSource();
        $this->assertEquals(new JSONWebServiceDatasource(new JSONWebServiceDataSourceConfig("https://hello.com"),
            new BasicAuthenticationCredentials("Bob", "password")), $dataSource);

    }

}