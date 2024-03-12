<?php


namespace Kinintel\ValueObjects\Authentication\SQLDatabase;


use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinikit\Persistence\Database\Vendors\MySQL\MySQLDatabaseConnection;

class MySQLAuthenticationCredentials implements SQLDatabaseCredentials {

    /**
     * @var string
     */
    private $host;


    /**
     * @var string
     */
    private $socket;


    /**
     * @var string
     */
    private $database;


    /**
     * @var integer
     */
    private $port;


    /**
     * @var string
     */
    private $charset;


    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * MySQLAuthenticationCredentials constructor.
     * @param string $host
     * @param string $socket
     * @param string $database
     * @param int $port
     * @param string $charset
     * @param string $username
     * @param string $password
     */
    public function __construct($host = null, $socket = null,
                                $database = null, $port = null, $charset = null, $username = null, $password = null) {
        $this->host = $host;
        $this->socket = $socket;
        $this->database = $database;
        $this->port = $port;
        $this->charset = $charset;
        $this->username = $username;
        $this->password = $password;
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
    public function getSocket() {
        return $this->socket;
    }

    /**
     * @param string $socket
     */
    public function setSocket($socket) {
        $this->socket = $socket;
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
     * @return int
     */
    public function getPort() {
        return $this->port;
    }

    /**
     * @param int $port
     */
    public function setPort($port) {
        $this->port = $port;
    }

    /**
     * @return string
     */
    public function getCharset() {
        return $this->charset;
    }

    /**
     * @param string $charset
     */
    public function setCharset($charset) {
        $this->charset = $charset;
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
        if ($this->socket) $params["socket"] = $this->socket;
        if ($this->database) $params["database"] = $this->database;
        if ($this->port) $params["port"] = $this->port;
        if ($this->charset) $params["charset"] = $this->charset;
        if ($this->username) $params["username"] = $this->username;
        if ($this->password) $params["password"] = $this->password;

        return new MySQLDatabaseConnection($params);
    }

    public function query() {
        // TODO: Implement query() method.
    }
}