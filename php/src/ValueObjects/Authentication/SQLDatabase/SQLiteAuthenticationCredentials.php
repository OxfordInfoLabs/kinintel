<?php


namespace Kinintel\ValueObjects\Authentication\SQLDatabase;


use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinikit\Persistence\Database\Vendors\SQLite3\SQLite3DatabaseConnection;
use Kinintel\ValueObjects\Authentication\AuthenticationCredentials;

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

}