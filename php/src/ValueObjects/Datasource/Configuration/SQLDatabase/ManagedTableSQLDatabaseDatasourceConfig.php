<?php


namespace Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase;


/**
 * Fixed extension of the sql database datasource config which prescribes managed table
 * functionality.  Used by the snapshot and custom datasources.
 *
 * Class ManagedTableSQLDatabaseDatasourceConfig
 * @package Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase
 */
class ManagedTableSQLDatabaseDatasourceConfig extends SQLDatabaseDatasourceConfig {

    /**
     * Force true for manage table structure
     *
     * @return bool
     */
    public function isManageTableStructure() {
        return true;
    }

}