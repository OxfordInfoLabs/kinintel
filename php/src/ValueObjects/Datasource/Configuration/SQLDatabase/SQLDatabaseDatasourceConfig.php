<?php


namespace Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase;


use Kinintel\ValueObjects\Datasource\DatasourceConfig;

class SQLDatabaseDatasourceConfig implements DatasourceConfig {

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
    private $updatable;

    // Currently supported sources for the data using the authenticated connection.
    const SOURCE_TABLE = "table";
    const SOURCE_QUERY = "query";

    /**
     * SQLDatabaseDatasourceConfig constructor.
     *
     * @param string $source
     * @param string $tableName
     * @param string $query
     */
    public function __construct($source, $tableName = "", $query = "", $updatable = false) {
        $this->source = $source;
        $this->tableName = $tableName;
        $this->query = $query;
        $this->updatable = $updatable;
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
    public function isUpdatable() {
        return $this->updatable;
    }

    /**
     * @param bool $updatable
     */
    public function setUpdatable($updatable) {
        $this->updatable = $updatable;
    }


}