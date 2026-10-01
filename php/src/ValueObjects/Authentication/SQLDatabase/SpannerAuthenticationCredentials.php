<?php

namespace Kinintel\ValueObjects\Authentication\SQLDatabase;

use Kinikit\Core\Util\FunctionStringRewriter;
use Kinikit\Persistence\Database\Vendors\Google\Spanner\SpannerDatabaseConnection;

class SpannerAuthenticationCredentials implements SQLDatabaseCredentials {

    private string $instanceId;

    private string $databaseId;

    public function __construct($instanceId, $databaseId) {
        $this->instanceId = $instanceId;
        $this->databaseId = $databaseId;
    }

    public function returnDatabaseConnection() {
        return new SpannerDatabaseConnection([
            "instanceId" => $this->instanceId,
            "databaseId" => $this->databaseId
        ]);
    }

    public function query($sql, $parameterValues) {
        $sql = $this->parseSQL($sql, $parameterValues);
        $databaseConnection = $this->returnDatabaseConnection();
        return $databaseConnection->query($sql, $parameterValues);
    }

    private function parseSQL($sql, &$parameterValues) {
        $sql = FunctionStringRewriter::rewrite($sql, "GROUP_CONCAT", "STRING_AGG($1,$2)", [null, ","]);
        return $sql;
    }
}