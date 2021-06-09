<?php


namespace Kinintel\Objects\Datasource\SQLDatabase;


use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinintel\Exception\DatasourceNotUpdatableException;
use Kinintel\Exception\DatasourceUpdateException;
use Kinintel\Objects\Dataset\Dataset;
use Kinintel\Objects\Dataset\Tabular\SQLResultSetTabularDataset;
use Kinintel\Objects\Dataset\Tabular\TabularDataset;
use Kinintel\Objects\Datasource\BaseDatasource;
use Kinintel\Objects\Datasource\BaseUpdatableDatasource;
use Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor\SQLTransformationProcessor;
use Kinintel\Objects\Datasource\UpdatableDatasource;
use Kinintel\Objects\Datasource\UpdatableDatasourceTrait;
use Kinintel\ValueObjects\Authentication\SQLDatabase\MySQLAuthenticationCredentials;
use Kinintel\ValueObjects\Authentication\SQLDatabase\SQLiteAuthenticationCredentials;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Datasource\DatasourceUpdateConfig;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
use Kinintel\ValueObjects\Transformation\MultiSort\MultiSortTransformation;
use Kinintel\ValueObjects\Transformation\Paging\PagingTransformation;
use Kinintel\ValueObjects\Transformation\SQLDatabaseTransformation;
use Kinintel\ValueObjects\Transformation\Transformation;

class SQLDatabaseDatasource extends BaseUpdatableDatasource {

    /**
     * @var Transformation[]
     */
    private $transformations = [];


    /**
     * Cached array of transformation processors
     *
     * @var SQLTransformationProcessor[]
     */
    private $transformationProcessorInstances = [];


    /**
     * Cached DB connection for efficiency
     *
     * @var DatabaseConnection
     */
    private $dbConnection = null;

    /**
     * Return the config class for this datasource
     *
     * @return string
     */
    public function getConfigClass() {
        return SQLDatabaseDatasourceConfig::class;
    }

    /**
     * Return array of credential classes supported by this data source
     *
     * @return string[]
     */
    public function getSupportedCredentialClasses() {
        return [
            SQLiteAuthenticationCredentials::class,
            MySQLAuthenticationCredentials::class
        ];
    }

    /**
     * Always require authentication for SQL Database data sources
     *
     * @return bool
     */
    public function isAuthenticationRequired() {
        return true;
    }


    /**
     * Get supported transformation classes
     *
     * @return string[]
     */
    public function getSupportedTransformationClasses() {
        return [
            FilterTransformation::class,
            MultiSortTransformation::class,
            PagingTransformation::class
        ];
    }


    /**
     * Set transformation processor instances (testing purposes)
     *
     * @param SQLTransformationProcessor[] $transformationProcessorInstances
     */
    public function setTransformationProcessorInstances($transformationProcessorInstances) {
        $this->transformationProcessorInstances = $transformationProcessorInstances;
    }


    /**
     * Apply a transformation to the SQL database
     * provided it is of the right type
     *
     * @param Transformation $transformation
     * @param array $parameterValues
     * @return BaseDatasource|void
     */
    public function applyTransformation($transformation, $parameterValues = []) {

        if ($transformation instanceof SQLDatabaseTransformation) {
            $this->transformations[] = $transformation;
        }
        return $this;
    }


    /**
     * Materialise this dataset using a SQL query
     *
     * @param array $parameterValues
     * @return Dataset|void
     */
    public function materialiseDataset($parameterValues = []) {


        $query = $this->buildQuery();


        /**
         * @var DatabaseConnection $dbConnection
         */
        $dbConnection = $this->returnDatabaseConnection();
        $resultSet = $dbConnection->query($query->getSQL(), $query->getParameters());

        // Return a tabular dataset
        return new SQLResultSetTabularDataset($resultSet);
    }


    /**
     * Update this datasource using a supplied dataset and the update mode supplied.
     *
     * @param Dataset $dataset
     * @param string $updateMode
     *
     */
    public function update($dataset, $updateMode = UpdatableDatasource::UPDATE_MODE_ADD) {

        /**
         * @var DatasourceUpdateConfig
         */
        $updateConfig = $this->getUpdateConfig();

        // If no update config throw now.
        if (!$updateConfig) {
            throw new DatasourceNotUpdatableException($this);
        }

        if (!($dataset instanceof TabularDataset)) {
            throw new DatasourceUpdateException("SQL Database Datasources can only be updated with Tabular Datasets");
        }

        /**
         * @var SQLDatabaseDatasourceConfig $config
         */
        $config = $this->getConfig();

        if ($config->getSource() !== SQLDatabaseDatasourceConfig::SOURCE_TABLE) {
            throw new DatasourceUpdateException("Attempted to update a SQL datasource which does not have a table source");
        }

        // Get a db connection and the bulk data manager
        $dbConnection = $this->returnDatabaseConnection();
        $bulkDataManager = $dbConnection->getBulkDataManager();

        // Get all data from the dataset
        $allData = $dataset->getAllData();

        // Get insert columns from dataset
        $updateColumns = null;
        if ($dataset->getColumns()) {
            $updateColumns = ObjectArrayUtils::getMemberValueArrayForObjects("name", $dataset->getColumns());
        }

        switch ($updateMode) {
            case UpdatableDatasource::UPDATE_MODE_ADD:
                $bulkDataManager->insert($config->getTableName(), $allData, $updateColumns);
                break;
            case UpdatableDatasource::UPDATE_MODE_DELETE:
                $pks = array_map(function ($row) use ($updateConfig) {
                    $pkValue = [];
                    foreach ($updateConfig->getKeyFieldNames() as $keyFieldName) {
                        $pkValue[] = $row[$keyFieldName] ?? null;
                    }
                    return $pkValue;
                }, $allData);
                $bulkDataManager->delete($config->getTableName(), $pks, null);
                break;
            case UpdatableDatasource::UPDATE_MODE_REPLACE:
                $bulkDataManager->replace($config->getTableName(), $allData, $updateColumns);
                break;
        }
    }


    /**
     * Get a singleton db connection
     *
     * @return DatabaseConnection
     */
    private function returnDatabaseConnection() {
        if (!$this->dbConnection) {
            $this->dbConnection = $this->getAuthenticationCredentials()->returnDatabaseConnection();
        }

        return $this->dbConnection;
    }

    // Build SQL statement using configured settings
    private function buildQuery() {

        /**
         * @var SQLDatabaseDatasourceConfig $config
         */
        $config = $this->getConfig();

        // If a tabular based source, create base clause
        if ($config->getSource() == SQLDatabaseDatasourceConfig::SOURCE_TABLE) {
            $query = new SQLQuery("*", $config->getTableName());
        } else {
            $query = new SQLQuery("*", "(" . $config->getQuery() . ") A");
        }

        /**
         * Process each transformation
         *
         * @var $transformation SQLDatabaseTransformation
         */
        $previousTransformationsDescending = [];
        foreach ($this->transformations as $transformation) {
            $processorKey = $transformation->getSQLTransformationProcessorKey();
            $processor = $this->getTransformationProcessor($processorKey);
            $query = $processor->updateQuery($transformation, $query, $previousTransformationsDescending);
            $previousTransformationsDescending[] = $transformation;
        }

        return $query;

    }


    /**
     * Get a transformation processor, caching as applicable
     *
     * @param $key
     * @return SQLTransformationProcessor
     */
    private function getTransformationProcessor($key) {
        if (!isset($this->transformationProcessorInstances[$key])) {
            $this->transformationProcessorInstances[$key] = Container::instance()->getInterfaceImplementation(SQLTransformationProcessor::class, $key);
        }
        return $this->transformationProcessorInstances[$key] ?? null;
    }


}