<?php

namespace Kinintel\ValueObjects\DataProcessor\Configuration\Query;

class SQLQueryDataProcessorConfiguration {

    /**
     * @var string
     * @required
     */
    private $query;

    /**
     * @var string
     * @required
     */
    private $authenticationCredentialsKey;

    /**
     * @param string $query
     * @param string $authenticationCredentialsKey
     */
    public function __construct($query, $authenticationCredentialsKey) {
        $this->query = $query;
        $this->authenticationCredentialsKey = $authenticationCredentialsKey;
    }

    /**
     * @return string
     */
    public function getQuery() {
        return $this->query;
    }

    /**
     * @param string $query
     */
    public function setQuery($query) {
        $this->query = $query;
    }

    /**
     * @return string
     */
    public function getAuthenticationCredentialsKey() {
        return $this->authenticationCredentialsKey;
    }

    /**
     * @param string $authenticationCredentialsKey
     */
    public function setAuthenticationCredentialsKey($authenticationCredentialsKey) {
        $this->authenticationCredentialsKey = $authenticationCredentialsKey;
    }


}