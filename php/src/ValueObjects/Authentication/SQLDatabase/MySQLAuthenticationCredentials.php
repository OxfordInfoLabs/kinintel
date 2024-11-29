<?php


namespace Kinintel\ValueObjects\Authentication\SQLDatabase;


use Kinikit\Core\Util\FunctionStringRewriter;
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

    /**
     * @param string $sql
     * @param array $parameterValues
     * @return mixed
     */
    public function query($sql, $parameterValues = []) {

        $sql = $this->parseSQL($sql, $parameterValues);

        $databaseConnection = $this->returnDatabaseConnection();
        return $databaseConnection->query($sql, $parameterValues);

    }

    /**
     * @param string $sql
     * @param array $parameterValues
     * @return string
     */
    public function parseSQL($sql, &$parameterValues = []) {

        // Map functions
        if (!strpos($sql, "SEPARATOR")) {
            $sql = FunctionStringRewriter::rewrite($sql, "GROUP_CONCAT", "GROUP_CONCAT($1 SEPARATOR $2)", [null, "','"], $parameterValues);
        }

        $sql = FunctionStringRewriter::rewrite($sql, "EPOCH_SECONDS", "UNIX_TIMESTAMP($1)", [0], $parameterValues);
        $sql = FunctionStringRewriter::rewrite($sql, "IIF", "IF($1, $2, $3)", [0, 0, 0], $parameterValues);
        $sql = FunctionStringRewriter::rewrite($sql, "ROW_NUMBER", "ROW_NUMBER() OVER (ORDER BY $1...)", ["1=1"], $parameterValues);
        $sql = FunctionStringRewriter::rewrite($sql, "ROW_COUNT", "COUNT(*) OVER (PARTITION BY $1...)", ["null"], $parameterValues);
        $sql = FunctionStringRewriter::rewrite($sql, "TOTAL", "SUM($1) OVER (PARTITION BY $2...)", [1, "null"], $parameterValues);
        $sql = FunctionStringRewriter::rewrite($sql, "MAXIMUM", "MAX($1) OVER (PARTITION BY $2...)", [1, "null"], $parameterValues);
        $sql = FunctionStringRewriter::rewrite($sql, "MINIMUM", "MIN($1) OVER (PARTITION BY $2...)", [1, "null"], $parameterValues);
        $sql = FunctionStringRewriter::rewrite($sql, "AVERAGE", "AVG($1) OVER (PARTITION BY $2...)", [1, "null"], $parameterValues);
        $sql = FunctionStringRewriter::rewrite($sql, "PERCENT", "100 * $1 / SUM($1) OVER (PARTITION BY $2...)", [1,"null"], $parameterValues);

        // Handle custom aggregate functions
        $sql = FunctionStringRewriter::rewrite($sql, "COUNT_PERCENT", "100 * COUNT($1) / COUNT_TOTAL($1)", [0], $parameterValues);
        $sql = FunctionStringRewriter::rewrite($sql, "SUM_PERCENT", "100 * SUM($1) / SUM_TOTAL($1)", [0], $parameterValues);
        $sql = FunctionStringRewriter::rewrite($sql, "COUNT_TOTAL", "SUM(COUNT($1)) OVER ()", [0], $parameterValues);
        $sql = FunctionStringRewriter::rewrite($sql, "SUM_TOTAL", "SUM(SUM($1)) OVER ()", [0], $parameterValues);


        // Handle Internet Address Conversions
        $sql = FunctionStringRewriter::rewrite($sql, "IP_ADDRESS_TO_NUMBER", "CASE WHEN $1 LIKE '%:%' THEN (CAST(CONV(SUBSTR(HEX(INET6_ATON($1)), 1, 16), 16, 10) as DECIMAL(65))*18446744073709551616 + CAST(CONV(SUBSTR(HEX(INET6_ATON($1)), 17, 16), 16, 10) as DECIMAL(65))) ELSE INET_ATON($1) END", [], $parameterValues);
        $sql = FunctionStringRewriter::rewrite($sql, "IP_NUMBER_TO_ADDRESS", "CASE WHEN $1 LIKE '%:%' THEN NULL ELSE INET_NTOA($1) END", [], $parameterValues);

        return $sql;

    }
}