<?php


namespace Kinintel\ValueObjects\Authentication\SQLDatabase;


use Kinikit\Core\Util\FunctionStringRewriter;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinikit\Persistence\Database\Vendors\SQLite3\SQLite3DatabaseConnection;

class SQLiteAuthenticationCredentials implements SQLDatabaseCredentials {

    /**
     * @var string
     */
    private $filename;

    /**
     * SQLiteAuthenticationCredentials constructor.
     *
     * @param string $filename
     */
    public function __construct($filename) {
        $this->filename = $filename;
    }


    /**
     * @return string
     */
    public function getFilename() {
        return $this->filename;
    }

    /**
     * @param string $filename
     */
    public function setFilename($filename) {
        $this->filename = $filename;
    }

    /**
     * Return a database connection using installed configuration
     *
     * @return DatabaseConnection
     */
    public function returnDatabaseConnection() {
        return new SQLite3DatabaseConnection([
            "filename" => $this->filename
        ]);
    }

    public function query($sql, $parameterValues = []) {

        $sql = $this->parseSQL($sql, $parameterValues);

        $databaseConnection = $this->returnDatabaseConnection();
        return $databaseConnection->query($sql, $parameterValues);

    }

    public function parseSQL($sql, &$parameterValues = []) {

        // Map functions
        $sql = FunctionStringRewriter::rewrite($sql, "EPOCH_SECONDS", "STRFTIME('%s',$1)", [0]);
        $sql = FunctionStringRewriter::rewrite($sql, "ROW_NUMBER", "ROW_NUMBER() OVER (ORDER BY $1,$2)", ["1=1", "1=1"], $parameterValues);
        $sql = FunctionStringRewriter::rewrite($sql, "TOTAL", "SUM($1) OVER ()", [0], $parameterValues);
        $sql = FunctionStringRewriter::rewrite($sql, "PERCENT", "100 * $1 / SUM($1) OVER ()", [0], $parameterValues);
        $sql = FunctionStringRewriter::rewrite($sql, "ROW_COUNT", "COUNT(*) OVER ()", [0], $parameterValues);
        $sql = preg_replace("/(\W)RLIKE(\W)/", "$1REGEXP$2", $sql);

        // Handle custom aggregate functions
        $sql = FunctionStringRewriter::rewrite($sql, "COUNT_PERCENT", "100 * COUNT($1) / COUNT_TOTAL($1)", [0], $parameterValues);
        $sql = FunctionStringRewriter::rewrite($sql, "SUM_PERCENT", "100 * SUM($1) / SUM_TOTAL($1)", [0], $parameterValues);
        $sql = FunctionStringRewriter::rewrite($sql, "COUNT_TOTAL", "SUM(COUNT($1)) OVER ()", [0], $parameterValues);
        $sql = FunctionStringRewriter::rewrite($sql, "SUM_TOTAL", "SUM(SUM($1)) OVER ()", [0], $parameterValues);

        return $sql;
    }
}