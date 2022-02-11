<?php


namespace Kinintel\Test\ValueObjects\Authentication\WebService;


use Kinikit\Core\HTTP\Request\Headers;
use Kinikit\Core\HTTP\Request\Request;
use Kinintel\ValueObjects\Authentication\WebService\HTTPHeaderAuthenticationCredentials;
use Kinintel\ValueObjects\Authentication\WebService\QueryParameterAuthenticationCredentials;
use PHPUnit\Framework\TestCase;

class HTTPHeaderAuthenticationCredentialsTest extends TestCase {

    public function testProcessRequestReturnsSourceRequestWithAdditionalHeaders() {

        $credentials = new HTTPHeaderAuthenticationCredentials([
            "User" => "mark@test", "Pass" => "bingo"
        ]);

        $request = new Request("http://google.com", Request::METHOD_PATCH, ["param1" => "Pookie"], "BINGO WAS HIS NAME OH!", new Headers([
            Headers::CACHE_CONTROL => "normal"
        ]));

        $authRequest = $credentials->processRequest($request);

        $this->assertEquals($request, $authRequest);

        $this->assertEquals([Headers::CACHE_CONTROL => "normal", "User" => "mark@test", "Pass" => "bingo"], $request->getHeaders()->getHeaders());


    }

}