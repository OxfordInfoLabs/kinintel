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
     * Construct a managed table datasource
     *
     * @param string $source
     * @param string $tableName
     * @param string $query
     * @param Field[] $columns
     * @param boolean $pagingViaParameters
     * @param Index[] $indexes
     */
    public function __construct($source, $tableName = "", $query = "", $columns = [], $pagingViaParameters = false, $indexes = []) {
        parent::__construct($source, $tableName, $query, $columns, true, $pagingViaParameters);
        $this->indexes = $indexes;
    }

    /**
     * @var Index[]
     */
    private $indexes = [];

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