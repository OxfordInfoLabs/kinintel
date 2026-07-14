<?php

namespace Kinintel\ValueObjects\Authentication\SQLDatabase;

use Kinikit\Persistence\Database\Vendors\Google\BigQuery\BigQueryDatabaseConnection;
use Kinintel\ValueObjects\Authentication\Google\GoogleCloudCredentials;
use Kinintel\ValueObjects\Authentication\SQLDatabase\SQLDatabaseCredentials;

class BigQueryAuthenticationCredentials extends GoogleCloudCredentials implements SQLDatabaseCredentials {

    public function returnDatabaseConnection() {
        return new BigQueryDatabaseConnection([
            "jsonString" => $this->getJsonString()
        ]);
    }

    public function query($sql, $parameterValues = []) {
        $databaseConnection = $this->returnDatabaseConnection();
        return $databaseConnection->query($sql, $parameterValues);
    }

}