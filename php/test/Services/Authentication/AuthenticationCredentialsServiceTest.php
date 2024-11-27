<?php

namespace Kinintel\Services\Authentication;

use Kinikit\Core\DependencyInjection\Container;
use Kinintel\Objects\Authentication\AuthenticationCredentialsInstance;
use Kinintel\TestBase;

include_once "autoloader.php";

/**
 * Test cases for the authentication credentials service.
 *
 * Class AuthenticationCredentialsServiceTest
 * @package Kinintel\Services\Authentication
 */
class AuthenticationCredentialsServiceTest extends TestBase {

    /**
     * @var AuthenticationCredentialsService
     */
    private $service;

    public function setUp(): void {
        $this->service = Container::instance()->get(AuthenticationCredentialsService::class);
    }


    public function testCanGetFileStoredAuthenticationCredentialsInstanceByKey() {

        $credentialsInstance = $this->service->getCredentialsInstanceByKey("http-basic");
        $this->assertEquals(new AuthenticationCredentialsInstance("http-basic",
            "http-basic", [
                "username" => "joebloggs",
                "password" => "password1"
            ]), $credentialsInstance);

        $credentialsInstance = $this->service->getCredentialsInstanceByKey("http-query");
        $this->assertEquals(new AuthenticationCredentialsInstance("http-query",
            "http-query", [
                "accessKey" => "MYKEY",
                "secret" => "MYSECRET"
            ]), $credentialsInstance);

    }


    public function testCanGetStoreAndRetrieveDatabaseAuthenticationCredentials() {

        $credentialsInstance = new AuthenticationCredentialsInstance("db-http-basic", "http-basic", [
            "username" => "petersmith",
            "password" => "password3"
        ]);

        // Save credentials instance
        $this->service->saveCredentialsInstance($credentialsInstance);

        $instance = $this->service->getCredentialsInstanceByKey("db-http-basic");

        $this->assertEquals(new AuthenticationCredentialsInstance("db-http-basic",
            "http-basic", [
                "username" => "petersmith",
                "password" => "password3"
            ]), $instance);

    }


}