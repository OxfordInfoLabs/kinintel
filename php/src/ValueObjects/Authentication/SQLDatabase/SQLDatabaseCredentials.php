<?php


namespace Kinintel\ValueObjects\Authentication\SQLDatabase;

use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinintel\ValueObjects\Authentication\AuthenticationCredentials;

interface SQLDatabaseCredentials extends AuthenticationCredentials {

    /**
     * Get a database connection for the supplied credentials
     *
     * @return DatabaseConnection
     */
    public function returnDatabaseConnection();

    /**
     * Execute a query on the sql database for the supplied credentials
     *
     * @return mixed
     */
    public function query();

}