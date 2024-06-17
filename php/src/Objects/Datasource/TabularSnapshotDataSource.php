<?php


namespace Kinintel\Objects\Datasource;


use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\ManagedTableSQLDatabaseDatasourceConfigConfig;

/**
 * Trivial extension of the SQL Database Datasource to encode the configuration
 * as a managed table config for tabular snapshots
 *
 * Class TabularSnapshotDataSource
 * @package Kinintel\Objects\Datasource
 */
class TabularSnapshotDataSource extends SQLDatabaseDatasource {

    /**
     * Get the config class for the custom data source
     *
     * @return string
     */
    public function getConfigClass() {
        return ManagedTableSQLDatabaseDatasourceConfigConfig::class;
    }

}