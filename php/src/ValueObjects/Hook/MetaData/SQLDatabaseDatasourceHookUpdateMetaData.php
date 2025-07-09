<?php

namespace Kinintel\ValueObjects\Hook\MetaData;

use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinintel\ValueObjects\Hook\DatasourceHookUpdateMetaData;

class SQLDatabaseDatasourceHookUpdateMetaData implements DatasourceHookUpdateMetaData {


    /**
     * Last auto increment id.
     *
     * @var int
     */
    private int $lastAutoIncrementId;

    /**
     * Base connection
     *
     * @param DatabaseConnection $databaseConnection
     */
    public function __construct($databaseConnection = null) {
        $this->lastAutoIncrementId = $databaseConnection->getLastAutoIncrementId() ?? -1;
    }

    /**
     * @return int
     */
    public function getLastAutoIncrementId(): int {
        return $this->lastAutoIncrementId;
    }


}