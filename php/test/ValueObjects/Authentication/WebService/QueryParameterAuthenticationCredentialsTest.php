<?php


namespace Kinintel\ValueObjects\Authentication\WebService;

use Kinikit\Core\HTTP\Request\Headers;
use Kinikit\Core\HTTP\Request\Request;
use PHPUnit\Framework\TestCase;


class QueryParameterAuthenticationCredentialsTest extends TestCase {

    public function testSuppliedParametersAreAppliedAsParametersOnProcessRequest() {

        $credentials = new QueryParameterAuthenticationCredentials([
            "user" => "mark@test", "pass" => "bingo"
        ]);

        $request = new Request("http://google.com", Request::METHOD_PATCH, ["param1" => "Pookie"], "BINGO WAS HIS NAME OH!", new Headers([
            Headers::CACHE_CONTROL => "normal"
        ]));

        $authRequest = $credentials->processRequest($request);

        $this->assertEquals($request, $authRequest);

        $this->assertEquals(["param1" => "Pookie", "user" => "mark@test", "pass" => "bingo"], $request->getParameters());

    }


}