<?php

namespace Kinintel\ValueObjects\DataProcessor\Configuration\Query;

class SQLQueryDataProcessorConfiguration {

    /**
     * @var string
     * @requiredEither queries,scriptFilepath
     */
    private $query;

    /**
     * @var string[]
     */
    private $queries;

    private ?string $scriptFilepath;

    /**
     * @var string
     * @required
     */
    private $authenticationCredentialsKey;

    /**
     * @param string $query
     * @param string[] $queries
     * @param string $authenticationCredentialsKey
     */
    public function __construct($query = null, $queries = null, $scriptFilepath = null, $authenticationCredentialsKey = null) {
        $this->query = $query;
        $this->queries = $queries;
        $this->scriptFilepath = $scriptFilepath;
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

    public function getScriptFilepath(): ?string {
        return $this->scriptFilepath;
    }

    public function setScriptFilepath(?string $scriptFilepath): void {
        $this->scriptFilepath = $scriptFilepath;
    }

}