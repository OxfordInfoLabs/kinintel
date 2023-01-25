<?php


namespace Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase;


use Kinintel\ValueObjects\Datasource\Configuration\TabularResultsDatasourceConfig;

class SQLDatabaseDatasourceConfig extends TabularResultsDatasourceConfig {

    /**
     * @var string
     */
    private $source;

    /**
     * @var string
     */
    private $tableName;

    /**
     * @var string
     */
    private $query;


    /**
     * @var boolean
     */
    private $manageTableStructure;


    // Currently supported sources for the data using the authenticated connection.
    const SOURCE_TABLE = "table";
    const SOURCE_QUERY = "query";

    /**
     * SQLDatabaseDatasourceConfig constructor.
     *
     * @param string $source
     * @param string $tableName
     * @param string $query
     * @param Field[] $columns
     * @param boolean $manageTableStructure
     */
    public function __construct($source, $tableName = "", $query = "", $columns = [], $manageTableStructure = false) {
        parent::__construct($columns);
        $this->source = $source;
        $this->tableName = $tableName;
        $this->query = $query;
        $this->manageTableStructure = $manageTableStructure;
    }

    /**
     * @return string
     */
    public function getSource() {
        return $this->source;
    }

    /**
     * @param string $source
     */
    public function setSource($source) {
        $this->source = $source;
    }

    /**
     * @return string
     */
    public function getTableName() {
        return $this->tableName;
    }

    /**
     * @param string $tableName
     */
    public function setTableName($tableName) {
        $this->tableName = $tableName;
    }

    /**
     * @return string
     */
    public function getQuery() {
        return $this->query;
    }

    /**
     * @param string $query
     */
    public function setQuery($query) {
        $this->query = $query;
    }

    /**
     * @return bool
     */
    public function isManageTableStructure() {
        return $this->manageTableStructure;
    }

    /**
     * @param bool $manageTableStructure
     */
    public function setManageTableStructure($manageTableStructure) {
        $this->manageTableStructure = $manageTableStructure;
    }


}
