<?php


namespace Kinintel\Objects\Datasource;


use Kinikit\Persistence\Database\Vendors\SQLite3\SQLite3DatabaseConnection;
use Kinintel\Objects\Dataset\Tabular\SQLResultSetTabularDataset;
use Kinintel\Objects\Datasource\SQLDatabase\SQLDatabaseDatasource;
use Kinintel\ValueObjects\Authentication\DefaultDatasourceCredentials;
use Kinintel\ValueObjects\Authentication\SQLDatabase\SQLiteAuthenticationCredentials;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Datasource\DatasourceUpdateConfig;

/**
 * Default datasource which is used as fallback if a transformation cannot be fulfilled by
 * a given datasource (provided this datasource can handle it).
 *
 * Class DefaultDatasource
 * @package Kinintel\Objects\Datasource
 */
class DefaultDatasource extends SQLDatabaseDatasource {

    /**
     * @var Datasource
     */
    private $sourceDatasource;

    /**
     * @var string
     */
    private $tableName;

    /**
     * Table index
     *
     * @var int
     */
    private static $tableIndex = 0;

    /**
     * @var SQLiteAuthenticationCredentials
     *
     */
    private static $credentials;

    public function __construct($sourceDatasource) {
        $this->tableName = "table_" . ++self::$tableIndex;
        parent::__construct(new SQLDatabaseDatasourceConfig(SQLDatabaseDatasourceConfig::SOURCE_TABLE, $this->tableName, null),
            self::getCredentials(), new DatasourceUpdateConfig());
        $this->sourceDatasource = $sourceDatasource;

    }


    /**
     * Return the source datasource
     *
     * @return Datasource
     */
    public function returnSourceDatasource() {
        return $this->sourceDatasource;
    }


    /**
     * Materialise function overloaded to ensure that we create and populate the table first
     *
     * @param array $parameterValues
     * @return \Kinintel\Objects\Dataset\Dataset
     * @throws \Kinintel\Exception\MissingDatasourceAuthenticationCredentialsException
     */
    public function materialise($parameterValues = []) {

        // Firstly materialise the source datasource
        $sourceDataset = $this->sourceDatasource->materialise($parameterValues);

        /**
         * @var SQLite3DatabaseConnection $dbConnection
         */
        $dbConnection = $this->getAuthenticationCredentials()->returnDatabaseConnection();

        // Create a create string
        $columns = $sourceDataset->getColumns();
        $createColumns = [];
        foreach ($columns as $column) {
            $createColumns[] = $column->getName() . " INTEGER";
        }

        $dbConnection->execute("CREATE TABLE $this->tableName (" . join(",", $createColumns) . ")");

        // Update this data set with the source dataset.
        $this->update($sourceDataset);

        // Match columns in configuration with those from the source dataset
        $this->getConfig()->setColumns($columns);

        // Materialise this dataset
        return parent::materialise($parameterValues);

    }


    /**
     * Get the singleton credentials in use for this datasource
     *
     * @return SQLiteAuthenticationCredentials
     */
    public static function getCredentials() {

        if (!self::$credentials) {
            self::$credentials = new DefaultDatasourceCredentials();
        }

        return self::$credentials;
    }


}