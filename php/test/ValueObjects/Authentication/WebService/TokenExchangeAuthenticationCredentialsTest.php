<?php

namespace Kinintel\Test\ValueObjects\Authentication\WebService;

use Kinikit\Core\HTTP\Dispatcher\HttpRequestDispatcher;
use Kinikit\Core\HTTP\Request\Headers;
use Kinikit\Core\HTTP\Request\Request;
use Kinikit\Core\HTTP\Response\Response;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\ValueObjects\Authentication\Generic\TokenExchangeAuthenticationCredentials;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class TokenExchangeAuthenticationCredentialsTest extends TestCase {

    public function testCanExchangeAccessTokenForJWTTokenAndAddHeaderToRequest() {

        $requestDispatcher = MockObjectProvider::mock(HttpRequestDispatcher::class);

        $authCreds = new TokenExchangeAuthenticationCredentials("myExchangeEndPoint", ["header1" => "value1", "header2" => "value2"], '{"token" => "myAccessToken"}', "token");

        $mockExchangeRequest = new Request(
            url: "myExchangeEndPoint",
            payload: '{"token" => "myAccessToken"}',
            headers: new Headers(["header1" => "value1", "header2" => "value2"])
        );

        $mockExchangeResponse = MockObjectProvider::mock(Response::class);
        $mockExchangeResponse->returnValue("getBody", '{"token":"myJWTToken"}');

        $requestDispatcher->returnValue("dispatch", $mockExchangeResponse, [$mockExchangeRequest]);
        $authCreds->setRequestDispatcher($requestDispatcher);

        $request = new Request(
            "targetEndpoint",
            payload: '{"some" => "stuff"}',
            headers: new Headers(["bing" => "bong"])
        );

        $processedRequest = $authCreds->processRequest($request);

        $expectedRequest = new Request(
            "targetEndpoint",
            payload: '{"some" => "stuff"}',
            headers: new Headers(["bing" => "bong", "Authorization" => "Bearer myJWTToken"])
        );

        $this->assertEquals($expectedRequest, $processedRequest);

    }

}