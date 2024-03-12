<?php

namespace Kinintel\ValueObjects\Authentication\SQLDatabase;

use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinikit\Persistence\Database\Vendors\PostgreSQL\PostgreSQLDatabaseConnection;

class PostgreSQLAuthenticationCredentials implements SQLDatabaseCredentials {

    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $port;

    /**
     * @var string
     */
    private $database;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @param string $host
     * @param string $port
     * @param string $database
     * @param string $username
     * @param string $password
     */
    public function __construct($host = null, $port = null, $database = null, $username = null, $password = null) {
        $this->host = $host;
        $this->database = $database;
        $this->username = $username;
        $this->password = $password;
        $this->port = $port;
    }

    /**
     * @return string
     */
    public function getHost() {
        return $this->host;
    }

    /**
     * @param string $host
     */
    public function setHost($host) {
        $this->host = $host;
    }

    /**
     * @return string
     */
    public function getPort() {
        return $this->port;
    }

    /**
     * @param string $port
     */
    public function setPort($port) {
        $this->port = $port;
    }

    /**
     * @return string
     */
    public function getDatabase() {
        return $this->database;
    }

    /**
     * @param string $database
     */
    public function setDatabase($database) {
        $this->database = $database;
    }

    /**
     * @return string
     */
    public function getUsername() {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername($username) {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getPassword() {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword($password) {
        $this->password = $password;
    }


    /**
     * Return a database connection using installed configuration
     *
     * @return DatabaseConnection
     */
    public function returnDatabaseConnection() {

        $params = [];

        if ($this->host) $params["host"] = $this->host;
        if ($this->port) $params["port"] = $this->port;
        if ($this->database) $params["database"] = $this->database;
        if ($this->username) $params["username"] = $this->username;
        if ($this->password) $params["password"] = $this->password;

        return new PostgreSQLDatabaseConnection($params);
    }

    public function query() {
        // TODO: Implement query() method.
    }
}