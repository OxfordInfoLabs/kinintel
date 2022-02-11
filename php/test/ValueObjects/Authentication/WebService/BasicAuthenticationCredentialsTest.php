<?php

namespace Kinintel\ValueObjects\Authentication\WebService;

use Kinikit\Core\HTTP\Request\Headers;
use Kinikit\Core\HTTP\Request\Request;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class BasicAuthenticationCredentialsTest extends TestCase {

    public function testProcessRequestReturnsSourceRequestWithAdditionalHeader() {

        $credentials = new BasicAuthenticationCredentials("tommy", "brown");

        $request = new Request("http://google.com", Request::METHOD_PATCH, ["param1" => "Pookie"], "BINGO WAS HIS NAME OH!", new Headers([
            Headers::CACHE_CONTROL => "normal"
        ]));

        $authRequest = $credentials->processRequest($request);

        // Check original request was returned
        $this->assertEquals($request, $authRequest);

        // Check headers was doctored with basic authorisation
        $headers = $request->getHeaders();
        $this->assertEquals("Basic " . base64_encode("tommy:brown"), $headers->getHeaders()[Headers::AUTHORISATION]);


    }

}