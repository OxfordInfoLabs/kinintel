<?php

namespace Kinintel\ValueObjects\Authentication\Generic;

use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\HTTP\Dispatcher\HttpRequestDispatcher;
use Kinikit\Core\HTTP\Request\Headers;
use Kinikit\Core\HTTP\Request\Request;
use Kinintel\ValueObjects\Authentication\AuthenticationCredentials;

class TokenExchangeAuthenticationCredentials implements AuthenticationCredentials {

    private string $exchangeEndpoint;

    private array $headers;

    private string $payload;

    private string $tokenField;

    private HttpRequestDispatcher $requestDispatcher;

    /**
     * @param string $exchangeEndpoint
     * @param array $headers
     * @param string $payload
     */
    public function __construct(string $exchangeEndpoint, array $headers, string $payload, string $tokenField) {
        $this->exchangeEndpoint = $exchangeEndpoint;
        $this->headers = $headers;
        $this->payload = $payload;
        $this->tokenField = $tokenField;
        $this->requestDispatcher = Container::instance()->get(HttpRequestDispatcher::class);
    }

    /**
     * Get the JWT token and add the header
     *
     * @param Request $request
     * @return Request
     */
    public function processRequest(Request $request): Request {

        $tokenRequest = new Request(
            url: $this->exchangeEndpoint,
            payload: $this->payload,
            headers: new Headers($this->headers)
        );

        $response = $this->requestDispatcher->dispatch($tokenRequest);

        $token = json_decode($response->getBody(), true)[$this->tokenField] ?? null;

        $request->getHeaders()->set("Authorization", "Bearer $token");
        return $request;

    }

    public function getExchangeEndpoint(): string {
        return $this->exchangeEndpoint;
    }

    public function setExchangeEndpoint(string $exchangeEndpoint): void {
        $this->exchangeEndpoint = $exchangeEndpoint;
    }

    public function getHeaders(): array {
        return $this->headers;
    }

    public function setHeaders(array $headers): void {
        $this->headers = $headers;
    }

    public function getPayload(): string {
        return $this->payload;
    }

    public function setPayload(string $payload): void {
        $this->payload = $payload;
    }

    // For testing purposes
    public function setRequestDispatcher(HttpRequestDispatcher $requestDispatcher): void {
        $this->requestDispatcher = $requestDispatcher;
    }

}