<?php


namespace Kinintel\ValueObjects\Authentication;


use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinikit\Persistence\Database\Vendors\SQLite3\SQLite3DatabaseConnection;
use Kinintel\ValueObjects\Authentication\SQLDatabase\SQLiteAuthenticationCredentials;

class DefaultDatasourceCredentials extends SQLiteAuthenticationCredentials {

    /**
     * @var SQLite3DatabaseConnection
     */
    private $instance = null;

    // Construct as temporary SQLLite database
    public function __construct() {
        parent::__construct(":memory:");
    }


    /**
     * Return a database connection using installed configuration
     *
     * @return DatabaseConnection
     */
    public function returnDatabaseConnection() {

        if (!$this->instance)
            $this->instance = new SQLite3DatabaseConnection([
                "filename" => $this->getFilename()
            ]);

        return $this->instance;
    }

}