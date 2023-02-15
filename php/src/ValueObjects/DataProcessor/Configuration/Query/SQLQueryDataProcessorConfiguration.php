<?php

namespace Kinintel\ValueObjects\DataProcessor\Configuration\Query;

class SQLQueryDataProcessorConfiguration {

    /**
     * @var string
     * @requiredEither queries
     */
    private $query;

    /**
     * @var string[]
     */
    private $queries;

    /**
     * @var string
     * @required
     */
    private $authenticationCredentialsKey;

    /**
     * @param null $query
     * @param null $queries
     * @param null $authenticationCredentialsKey
     */
    public function __construct($query = null, $queries = null, $authenticationCredentialsKey = null) {
        $this->query = $query;
        $this->queries = $queries;
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
     * @return string[]
     */
    public function getQueries() {
        return $this->queries;
    }

    /**
     * @param string[] $queries
     */
    public function setQueries($queries) {
        $this->queries = $queries;
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