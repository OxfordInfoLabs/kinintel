<?php


namespace Kinintel\Objects\Datasource\SQLDatabase;


use Cassandra\Table;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Template\TemplateParser;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinikit\Core\Validation\Validator;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinikit\Persistence\Database\Exception\SQLException;
use Kinikit\Persistence\Database\Generator\TableDDLGenerator;
use Kinikit\Persistence\Database\MetaData\TableColumn;
use Kinikit\Persistence\Database\MetaData\TableMetaData;
use Kinintel\Exception\DatasourceNotUpdatableException;
use Kinintel\Exception\DatasourceUpdateException;
use Kinintel\Objects\Dataset\Dataset;
use Kinintel\Objects\Dataset\Tabular\SQLResultSetTabularDataset;
use Kinintel\Objects\Dataset\Tabular\TabularDataset;
use Kinintel\Objects\Datasource\BaseDatasource;
use Kinintel\Objects\Datasource\BaseUpdatableDatasource;
use Kinintel\Objects\Datasource\DefaultDatasource;
use Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor\SQLTransformationProcessor;
use Kinintel\Objects\Datasource\UpdatableDatasource;
use Kinintel\Objects\Datasource\UpdatableDatasourceTrait;
use Kinintel\Services\Dataset\DatasetService;
use Kinintel\Services\Datasource\DatasourceService;
use Kinintel\ValueObjects\Authentication\AuthenticationCredentials;
use Kinintel\ValueObjects\Authentication\SQLDatabase\MySQLAuthenticationCredentials;
use Kinintel\ValueObjects\Authentication\SQLDatabase\SQLiteAuthenticationCredentials;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Datasource\DatasourceUpdateConfig;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Transformation\Columns\ColumnsTransformation;
use Kinintel\ValueObjects\Transformation\Filter\FilterTransformation;
use Kinintel\ValueObjects\Transformation\Formula\FormulaTransformation;
use Kinintel\ValueObjects\Transformation\Join\JoinTransformation;
use Kinintel\ValueObjects\Transformation\MultiSort\MultiSortTransformation;
use Kinintel\ValueObjects\Transformation\Paging\PagingTransformation;
use Kinintel\ValueObjects\Transformation\SQLDatabaseTransformation;
use Kinintel\ValueObjects\Transformation\Summarise\SummariseTransformation;
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
     * @var TableDDLGenerator
     */
    private $tableDDLGenerator;


    /**
     *
     */
    const FIELD_TYPE_SQL_TYPE_MAP = [
        Field::TYPE_STRING => TableColumn::SQL_VARCHAR,
        Field::TYPE_INTEGER => TableColumn::SQL_INTEGER,
        Field::TYPE_FLOAT => TableColumn::SQL_FLOAT,
        Field::TYPE_DATE => TableColumn::SQL_DATE,
        Field::TYPE_DATE_TIME => TableColumn::SQL_DATE_TIME
    ];

    /**
     * SQLDatabaseDatasource constructor.
     *
     * @param SQLDatabaseDatasourceConfig $config
     * @param AuthenticationCredentials $authenticationCredentials
     * @param DatasourceUpdateConfig $updateConfig
     * @param Validator $validator
     * @param TableDDLGenerator $tableDDLGenerator
     */
    public function __construct($config, $authenticationCredentials, $updateConfig, $validator = null, $tableDDLGenerator = null) {
        parent::__construct($config, $authenticationCredentials, $updateConfig, $validator);
        $this->tableDDLGenerator = $tableDDLGenerator ?? new TableDDLGenerator();
    }


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
            PagingTransformation::class,
            SummariseTransformation::class,
            JoinTransformation::class,
            ColumnsTransformation::class,
            FormulaTransformation::class
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

        $dataSource = $this;

        if ($transformation instanceof SQLDatabaseTransformation) {

            // Apply the transformation using the processor for this transformation
            $processorKey = $transformation->getSQLTransformationProcessorKey();
            $processor = $this->getTransformationProcessor($processorKey);
            $dataSource = $processor->applyTransformation($transformation, $dataSource, $parameterValues);

            $this->transformations[] = $transformation;
        }
        return $dataSource;
    }


    // Unapply the last transformation
    public function unapplyLastTransformation() {
        array_pop($this->transformations);
    }


    /**
     * Return the current set of transformations
     *
     * @return Transformation[]
     */
    public function returnTransformations() {
        return $this->transformations;
    }


    /**
     * Materialise this dataset using a SQL query
     *
     * @param array $parameterValues
     * @return Dataset|void
     */
    public function materialiseDataset($parameterValues = []) {

        $query = $this->buildQuery($parameterValues);

        /**
         * @var DatabaseConnection $dbConnection
         */
        $dbConnection = $this->returnDatabaseConnection();

        $resultSet = $dbConnection->query($query->getSQL(), $query->getParameters());

        // Return a tabular dataset
        return new SQLResultSetTabularDataset($resultSet, $this->getConfig()->returnEvaluatedColumns($parameterValues));
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


        // Only continue if some data to process
        if (sizeof($allData) > 0) {

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

                    $bulkDataManager->delete($config->getTableName(), $allData);
                    break;
                case UpdatableDatasource::UPDATE_MODE_REPLACE:
                    $bulkDataManager->replace($config->getTableName(), $allData, $updateColumns);
                    break;
            }

        }
    }

    /**
     * Modify table structure according to passed fields and optionally keyFieldNames referencing items in
     * the fields array for compiling a primary key
     *
     * @param Field[] $fields
     * @param string[] $keyFieldNames
     */
    public function modifyTableStructure($fields, $keyFieldNames = []) {

        // Construct the column array we need
        $columns = [];
        foreach ($fields as $field) {
            $type = self::FIELD_TYPE_SQL_TYPE_MAP[$field->getType()] ?? TableColumn::SQL_VARCHAR;
            $columns[] = new TableColumn($field->getName(), $type, null, null, null, in_array($field->getName(), $keyFieldNames));
        }

        $newMetaData = new TableMetaData($this->getConfig()->getTableName(), $columns);

        // Check to see whether the table already exists
        $sql = "";
        $databaseConnection = $this->returnDatabaseConnection();
        try {
            $previousMetaData = $this->dbConnection->getTableMetaData($this->getConfig()->getTableName());
            $sql = $this->tableDDLGenerator->generateTableModifySQL($previousMetaData, $newMetaData, $databaseConnection);
        } catch (SQLException $e) {
            $sql = $this->tableDDLGenerator->generateTableCreateSQL($newMetaData, $databaseConnection);
        }

        if (trim($sql))
            $databaseConnection->executeScript($sql);


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
    public function buildQuery($parameterValues = []) {

        /**
         * @var SQLDatabaseDatasourceConfig $config
         */
        $config = $this->getConfig();

        // If a tabular based source, create base clause
        if ($config->getSource() == SQLDatabaseDatasourceConfig::SOURCE_TABLE) {
            $query = new SQLQuery("*", $config->getTableName());
        } else {
            /**
             * @var TemplateParser $templateParser
             */
            $templateParser = Container::instance()->get(TemplateParser::class);
            $queryString = $templateParser->parseTemplateText($config->getQuery(), $parameterValues);

            $query = new SQLQuery("*", "(" . $queryString . ") A");
        }


        /**
         * Process each transformation
         *
         * @var $transformation SQLDatabaseTransformation
         */
        foreach ($this->transformations as $transformation) {
            $processorKey = $transformation->getSQLTransformationProcessorKey();
            $processor = $this->getTransformationProcessor($processorKey);
            $query = $processor->updateQuery($transformation, $query, $parameterValues, $this);
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
