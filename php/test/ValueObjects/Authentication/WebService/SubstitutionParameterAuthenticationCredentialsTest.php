<?php


namespace Kinintel\Test\ValueObjects\Authentication\WebService;


use Kinikit\Core\HTTP\Request\Headers;
use Kinikit\Core\HTTP\Request\Request;
use Kinintel\ValueObjects\Authentication\WebService\SubstitutionParameterAuthenticationCredentials;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class SubstitutionParameterAuthenticationCredentialsTest extends TestCase {

    public function testSubstitutionParametersAreAppliedToURL() {
        $credentials = new SubstitutionParameterAuthenticationCredentials(["username" => "test", "password" => 12345]);

        $request = new Request("https://myapplication.com/[[username]]?password=[[password]]");
        $updated = $credentials->processRequest($request);

        $this->assertEquals("https://myapplication.com/test?password=12345", $updated->getUrl());

    }

    public function testSubstitutionParametersAreAppliedToPayload() {
        $credentials = new SubstitutionParameterAuthenticationCredentials(["username" => "test", "password" => 12345]);

        $request = new Request("https://myapplication.com", Request::METHOD_GET, [], "username:[[username]],password:[[password]]");
        $updated = $credentials->processRequest($request);

        $this->assertEquals("username:test,password:12345", $updated->getPayload());

    }

    public function testSubstitutionParametersAreAppliedToHeaders() {
        $credentials = new SubstitutionParameterAuthenticationCredentials(["username" => "test", "password" => 12345]);

        $request = new Request("https://myapplication.com", Request::METHOD_GET, [], null, new Headers([
            "header1" => "[[username]]",
            "header2" => "pass:[[password]]"
        ]));
        $updated = $credentials->processRequest($request);

        $this->assertEquals("test", $updated->getHeaders()->getHeaders()["header1"]);
        $this->assertEquals("pass:12345", $updated->getHeaders()->getHeaders()["header2"]);


    }

}