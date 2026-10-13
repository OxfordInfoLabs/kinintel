<?php

namespace Kinintel\ValueObjects\Authentication\SQLDatabase;

use Kinikit\Persistence\Database\Vendors\Google\BigQuery\BigQueryDatabaseConnection;

class BigQueryAuthenticationCredentials implements SQLDatabaseCredentials {

    public function returnDatabaseConnection() {
        return new BigQueryDatabaseConnection();
    }

    public function query($sql, $parameterValues = []) {
        $databaseConnection = $this->returnDatabaseConnection();
        return $databaseConnection->query($sql, $parameterValues);
    }

}