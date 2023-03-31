<?php

namespace Kinintel\Objects\Datasource;

use Kinikit\Core\Validation\FieldValidationError;
use Kinintel\Exception\InvalidDatasourceAuthenticationCredentialsException;
use Kinintel\Exception\InvalidDatasourceConfigException;
use Kinintel\Exception\MissingDatasourceAuthenticationCredentialsException;
use Kinintel\Test\Objects\Datasource\TestAuthenticationCredentialsAlt;
use Kinintel\ValueObjects\Datasource\Configuration\WebService\WebserviceDataSourceConfig;
use Kinintel\ValueObjects\Datasource\DatasourceConfig;

include_once "autoloader.php";

class DatasourceTest extends \PHPUnit\Framework\TestCase {

    public function testIfNullConfigClassSuppliedExceptionRaisedIfConfigIsSupplied() {

        $dataSource = new TestDatasource();

        try {
            $dataSource->setConfig(new TestDatasourceConfig());
            $this->fail("Should have thrown here");
        } catch (InvalidDatasourceConfigException $e) {
            $this->assertEquals(new FieldValidationError("config", "wrongtype", "Config supplied to data source when none is required"), $e->getValidationErrors()["config"]["wrongtype"]);
            $this->assertTrue(true);
        }

    }

    public function testIfConfigOfWrongTypeSuppliedExceptionRaised() {

        $dataSource = new TestDatasource(WebserviceDataSourceConfig::class);

        try {
            $dataSource->setConfig(new TestDatasourceConfig());
            $this->fail("Should have thrown here");
        } catch (InvalidDatasourceConfigException $e) {
            $this->assertEquals(new FieldValidationError("config", "wrongtype", "Config supplied is of wrong type for data source"), $e->getValidationErrors()["config"]["wrongtype"]);
            $this->assertTrue(true);
        }

    }


    public function testIfInvalidConfigOfCorrectTypeSuppliedExceptionRaised() {

        $dataSource = new TestDatasource(TestDatasourceConfig::class);

        $config = new TestDatasourceConfig();

        try {
            $dataSource->setConfig($config);
            $this->fail("Should have thrown here");
        } catch (InvalidDatasourceConfigException $e) {
            $this->assertEquals(new FieldValidationError("property", "required", "This field is required"), $e->getValidationErrors()["property"]["required"]);
            $this->assertTrue(true);
        }


        // Successful one
        $config = new TestDatasourceConfig("Bingo");
        $dataSource->setConfig($config);


    }


    public function testIfUnsupportedAuthenticationCredentialsSuppliedExceptionRaised() {

        $dataSource = new TestDatasource(DatasourceConfig::class);

        try {
            $dataSource->setAuthenticationCredentials(new TestAuthenticationCredentials());
            $this->fail("Should have thrown here");
        } catch (InvalidDatasourceAuthenticationCredentialsException $e) {
            $this->assertEquals(new FieldValidationError("authenticationCredentials", "wrongtype", "Authentication credentials supplied are of wrong type for data source"), $e->getValidationErrors()["authenticationCredentials"]["wrongtype"]);
            $this->assertTrue(true);
        }

        $dataSource = new TestDatasource(DatasourceConfig::class, [TestAuthenticationCredentialsAlt::class]);

        try {
            $dataSource->setAuthenticationCredentials(new TestAuthenticationCredentials());
            $this->fail("Should have thrown here");
        } catch (InvalidDatasourceAuthenticationCredentialsException $e) {
            $this->assertEquals(new FieldValidationError("authenticationCredentials", "wrongtype", "Authentication credentials supplied are of wrong type for data source"), $e->getValidationErrors()["authenticationCredentials"]["wrongtype"]);
            $this->assertTrue(true);
        }
    }


    public function testIfInvalidCredentialsOfCorrectTypeSuppliedExceptionRaised() {

        $dataSource = new TestDatasource(TestDatasourceConfig::class, [TestAuthenticationCredentials::class]);

        $credentials = new TestAuthenticationCredentials();

        try {
            $dataSource->setAuthenticationCredentials($credentials);
            $this->fail("Should have thrown here");
        } catch (InvalidDatasourceAuthenticationCredentialsException $e) {
            $this->assertEquals(new FieldValidationError("username", "required", "This field is required"), $e->getValidationErrors()["username"]["required"]);
            $this->assertTrue(true);
        }


        // Successful one
        $credentials = new TestAuthenticationCredentials("marko");
        $dataSource->setAuthenticationCredentials($credentials);


    }


    public function testIfNoCredentialsSuppliedWhenRequiredExceptionIsRaisedOnMaterialise() {
        $dataSource = new TestDatasource(TestDatasourceConfig::class, [TestAuthenticationCredentials::class]);

        try {
            $dataSource->materialise();
            $this->fail("Should have thrown here");
        } catch (MissingDatasourceAuthenticationCredentialsException $e) {
            $this->assertTrue(true);
        }


        // OK if not required
        $dataSource = new TestDatasource(TestDatasourceConfig::class, [TestAuthenticationCredentials::class], false);
        $dataSource->materialise();

    }


}