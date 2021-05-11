<?php


namespace Kinintel\Objects\Datasource\SQLDatabase;


use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinintel\Objects\Dataset\Dataset;
use Kinintel\Objects\Dataset\Tabular\SQLResultSetTabularDataset;
use Kinintel\Objects\Datasource\Datasource;
use Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor\SQLTransformationProcessor;
use Kinintel\ValueObjects\Authentication\SQLDatabase\MySQLAuthenticationCredentials;
use Kinintel\ValueObjects\Authentication\SQLDatabase\SQLiteAuthenticationCredentials;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Transformation\SQLDatabaseTransformation;
use Kinintel\ValueObjects\Transformation\Transformation;

class SQLDatabaseDatasource extends Datasource {

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
     * @return Datasource|void
     */
    public function applyTransformation($transformation) {

        if ($transformation instanceof SQLDatabaseTransformation) {
            $this->transformations[] = $transformation;
        }
        return $this;
    }


    /**
     * Materialise this dataset using a SQL query
     *
     * @return Dataset|void
     */
    public function materialiseDataset() {


        $query = $this->buildQuery();

        /**
         * @var DatabaseConnection $dbConnection
         */
        $dbConnection = $this->getAuthenticationCredentials()->returnDatabaseConnection();
        $resultSet = $dbConnection->query($query->getSql(), $query->getParameters());

        // Return a tabular dataset
        return new SQLResultSetTabularDataset($resultSet);
    }

    // Build SQL statement using configured settings
    private function buildQuery() {

        /**
         * @var SQLDatabaseDatasourceConfig $config
         */
        $config = $this->getConfig();

        // If a tabular based source, create base clause
        if ($config->getSource() == SQLDatabaseDatasourceConfig::SOURCE_TABLE) {
            $query = new SQLQuery("SELECT * FROM " . $config->getTableName());
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