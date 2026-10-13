<?php

namespace Kinintel\ValueObjects\Authentication\SQLDatabase;

use Kinikit\Persistence\Database\Vendors\Google\BigQuery\BigQueryDatabaseConnection;
use Kinintel\ValueObjects\Authentication\Google\GoogleCloudCredentials;

class BigQueryAuthenticationCredentials implements SQLDatabaseCredentials {

    public function returnDatabaseConnection() {
        return new BigQueryDatabaseConnection();
    }

    public function query($sql, $parameterValues = []) {
        $databaseConnection = $this->returnDatabaseConnection();
        return $databaseConnection->query($sql, $parameterValues);
    }

}