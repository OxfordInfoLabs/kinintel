<?php

namespace Kinintel\ValueObjects\Datasource\Configuration;

use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\Index;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\ManagedTableSQLDatabaseDatasourceConfigConfig;

interface IndexableDatasourceConfig {


    /**
     * @param Index[] $indexes
     */
    public function setIndexes($indexes);

    /**
     * @return Index[]
     */
    public function getIndexes();
}