<?php


namespace Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase;


use Kinintel\ValueObjects\Datasource\Configuration\IndexableDatasourceConfig;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateField;

/**
 * Fixed extension of the sql database datasource config which prescribes managed table
 * functionality.  Used by the snapshot and custom datasources.
 *
 * Class ManagedTableSQLDatabaseDatasourceConfig
 * @package Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase
 */
class ManagedTableSQLDatabaseDatasourceConfig extends SQLDatabaseDatasourceConfig implements IndexableDatasourceConfig {

    /**
     * @var Index[]
     */
    private $indexes = [];


    /**
     * Construct a managed table datasource
     *
     * @param string $source
     * @param string $tableName
     * @param string $query
     * @param DatasourceUpdateField[] $columns
     * @param boolean $pagingViaParameters
     * @param Index[] $indexes
     */
    public function __construct($source, $tableName = "", $query = "", $columns = [], $pagingViaParameters = false, $indexes = []) {
        parent::__construct($source, $tableName, $query, $columns, true, $pagingViaParameters);
        $this->indexes = $indexes;
    }


    /**
     * @return Index[]
     */
    public function getIndexes() {
        return $this->indexes;
    }

    /**
     * @param Index[] $indexes
     */
    public function setIndexes($indexes) {
        $this->indexes = $indexes;
    }


    /**
     * Force true for manage table structure
     *
     * @return bool
     */
    public function isManageTableStructure() {
        return true;
    }

}