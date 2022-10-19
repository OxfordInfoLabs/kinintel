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
use Kinikit\Persistence\Database\MetaData\UpdatableTableColumn;
use Kinintel\Exception\DatasourceNotUpdatableException;
use Kinintel\Exception\DatasourceUpdateException;
use Kinintel\Objects\Dataset\Dataset;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
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
use Kinintel\Services\Util\ParameterisedStringEvaluator;
use Kinintel\ValueObjects\Authentication\AuthenticationCredentials;
use Kinintel\ValueObjects\Authentication\SQLDatabase\MySQLAuthenticationCredentials;
use Kinintel\ValueObjects\Authentication\SQLDatabase\SQLiteAuthenticationCredentials;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Datasource\DatasourceUpdateConfig;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateField;
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
     * @var bool
     */
    private $hasOriginalColumns = true;

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
    protected $tableDDLGenerator;


    /**
     * @var string[]
     */
    private static $additionalCredentialClasses = [];


    /**
     * Mappings of Types to SQL types
     */
    const FIELD_TYPE_SQL_TYPE_MAP = [
        Field::TYPE_STRING => TableColumn::SQL_VARCHAR,
        Field::TYPE_MEDIUM_STRING => TableColumn::SQL_VARCHAR,
        Field::TYPE_INTEGER => TableColumn::SQL_INTEGER,
        Field::TYPE_FLOAT => TableColumn::SQL_FLOAT,
        Field::TYPE_DATE => TableColumn::SQL_DATE,
        Field::TYPE_DATE_TIME => TableColumn::SQL_DATE_TIME,
        Field::TYPE_ID => TableColumn::SQL_INTEGER,
        Field::TYPE_LONG_STRING => TableColumn::SQL_LONGBLOB
    ];

    const FIELD_TYPE_LENGTH_MAP = [
        Field::TYPE_STRING => 255,
        Field::TYPE_MEDIUM_STRING => 2000
    ];

    const FIELD_SQL_TYPE_TYPE_MAP = [
        TableColumn::SQL_DOUBLE => Field::TYPE_FLOAT,
        TableColumn::SQL_DATE_TIME => Field::TYPE_DATE_TIME,
        TableColumn::SQL_DATE => Field::TYPE_DATE,
        TableColumn::SQL_INT => Field::TYPE_INTEGER,
        TableColumn::SQL_VARCHAR => [
            0 => Field::TYPE_STRING,
            255 => Field::TYPE_MEDIUM_STRING,
            2000 => Field::TYPE_LONG_STRING
        ],
        TableColumn::SQL_BIGINT => Field::TYPE_INTEGER,
        TableColumn::SQL_BLOB => Field::TYPE_LONG_STRING,
        TableColumn::SQL_LONGBLOB => Field::TYPE_LONG_STRING,
        TableColumn::SQL_DECIMAL => Field::TYPE_FLOAT,
        TableColumn::SQL_REAL => Field::TYPE_FLOAT,
        TableColumn::SQL_FLOAT => Field::TYPE_FLOAT,
        TableColumn::SQL_SMALLINT => Field::TYPE_INTEGER,
        TableColumn::SQL_INTEGER => Field::TYPE_INTEGER,
        TableColumn::SQL_TIME => Field::TYPE_INTEGER,
        TableColumn::SQL_TIMESTAMP => Field::TYPE_DATE_TIME,
        TableColumn::SQL_UNKNOWN => Field::TYPE_STRING
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
    public function __construct($config, $authenticationCredentials, $updateConfig, $validator = null, $tableDDLGenerator = null, $instanceKey = null,
                                $instanceTitle = null) {
        parent::__construct($config, $authenticationCredentials, $updateConfig, $validator, $instanceKey, $instanceTitle);
        $this->tableDDLGenerator = $tableDDLGenerator ?? new TableDDLGenerator();
    }


    /**
     * Add a credentials class statically
     *
     * @param $className
     */
    public static function addCredentialsClass($className) {
        self::$additionalCredentialClasses[] = $className;
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
        return array_merge([
            SQLiteAuthenticationCredentials::class,
            MySQLAuthenticationCredentials::class
        ], self::$additionalCredentialClasses);
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
     * @param null $pagingTransformation
     * @return BaseDatasource|void
     */
    public function applyTransformation($transformation, $parameterValues = [], $pagingTransformation = null) {

        $dataSource = $this;

        if ($transformation instanceof SQLDatabaseTransformation) {

            // Negate original columns if a none filter / sort transformation encountered.
            if (!($transformation instanceof FilterTransformation || $transformation instanceof MultiSortTransformation || $transformation instanceof PagingTransformation))
                $this->hasOriginalColumns = false;

            // Apply the transformation using the processor for this transformation
            $processorKey = $transformation->getSQLTransformationProcessorKey();
            $processor = $this->getTransformationProcessor($processorKey);
            $dataSource = $processor->applyTransformation($transformation, $dataSource, $parameterValues, $pagingTransformation);

            // If same datasource apply the transformation
            if ($dataSource === $this) {
                $this->transformations[] = $transformation;
            }
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
     * @return SQLResultSetTabularDataset
     */
    public function materialiseDataset($parameterValues = []) {


        $query = $this->buildQuery($parameterValues);


        /**
         * @var DatabaseConnection $dbConnection
         */
        $dbConnection = $this->returnDatabaseConnection();

        Logger::log($query->getParameters());
        Logger::log($query->getSQL());

        $resultSet = $dbConnection->query($query->getSQL(), $query->getParameters());

        // Grab columns
        $columns = $this->getConfig()->returnEvaluatedColumns($parameterValues);

        // If no explicit columns and table based query with no column changing transformations
        // Generate explicit columns to allow for dataset update.
        if ($this->getConfig()->getSource() == "table" && !$columns && $this->hasOriginalColumns) {

            $columns = [];
            $tableMetaData = $dbConnection->getTableMetaData($this->getConfig()->getTableName());

            foreach ($tableMetaData->getColumns() as $column) {

                $columnSpec = self::FIELD_SQL_TYPE_TYPE_MAP[$column->getType()] ?? Field::TYPE_STRING;
                if (is_array($columnSpec)) {
                    foreach ($columnSpec as $length => $item) {
                        if ($column->getLength() > $length) {
                            $fieldType = $item;
                        }
                    }
                } else {
                    $fieldType = $columnSpec;
                }

                $columns[] = new Field($column->getName(), null, null, $fieldType,
                    $column->isPrimaryKey());
            }
        }


        // Return a tabular dataset
        return new SQLResultSetTabularDataset($resultSet, $columns);
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

        // Update mapped field data
        $dataset = $this->updateMappedFieldData(new ArrayTabularDataset($dataset->getColumns(), $allData), $updateMode);

        // Only continue if some data to process
        if (sizeof($allData) > 0) {

            // Get insert columns from dataset
            $updateColumns = null;
            if ($dataset->getColumns()) {
                $updateColumns = ObjectArrayUtils::getMemberValueArrayForObjects("name", $dataset->getColumns());
            }

            // Ensure we don't exceed the batch size.
            if ($updateColumns) {
                $batchSize = 50 / ceil(sizeof($updateColumns) / 15);
                $bulkDataManager->setBatchSize($batchSize);
            }

            switch ($updateMode) {
                case UpdatableDatasource::UPDATE_MODE_ADD:
                    $bulkDataManager->insert($config->getTableName(), $allData, $updateColumns);
                    break;
                case UpdatableDatasource::UPDATE_MODE_DELETE:
                    $bulkDataManager->delete($config->getTableName(), $allData, $updateColumns);
                    break;
                case UpdatableDatasource::UPDATE_MODE_REPLACE:
                    $bulkDataManager->replace($config->getTableName(), $allData, $updateColumns);
                    break;
                case UpdatableDatasource::UPDATE_MODE_UPDATE:
                    $bulkDataManager->update($config->getTableName(), $allData, $updateColumns);
                    break;

            }

        }
    }

    /**
     * Event method called when a parent datasource instance is saved to provide
     * an opportunity to update e.g. structural stuff based on updated config.
     *
     * The default behaviour here is to update fields
     *
     */
    public function onInstanceSave() {
        $this->updateFields($this->getConfig()->getColumns());
    }


    /**
     * Handle instance delete when a parent datasource instance is deleted.
     *
     * @return mixed|void
     */
    public function onInstanceDelete() {
        $databaseConnection = $this->returnDatabaseConnection();
        $dropSQL = $this->tableDDLGenerator->generateTableDropSQL($this->getConfig()->getTableName());
        $databaseConnection->executeScript($dropSQL);
    }


    // Close this database connection
    public function closeDatabaseConnection() {
        $this->returnDatabaseConnection()->close();
    }

    /**
     * Get a singleton db connection
     *
     * @return DatabaseConnection
     */
    public function returnDatabaseConnection() {
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

        /**
         * @var ParameterisedStringEvaluator $parameterisedStringEvaluator
         */
        $parameterisedStringEvaluator = Container::instance()->get(ParameterisedStringEvaluator::class);

        // If a tabular based source, create base clause
        if ($config->getSource() == SQLDatabaseDatasourceConfig::SOURCE_TABLE) {
            $tableName = $parameterisedStringEvaluator->evaluateString($config->getTableName(), [], $parameterValues);
            $query = new SQLQuery("*", $tableName);
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


    /**
     * Update fields (opportunity for datasource to perform any required modifications)
     *
     */
    protected function updateFields($fields) {

        // Construct the column array we need
        $columns = [];
        foreach ($fields as $field) {
            $fieldType = $field->getType() ?? Field::TYPE_STRING;
            $type = self::FIELD_TYPE_SQL_TYPE_MAP[$fieldType] ?? TableColumn::SQL_VARCHAR;
            $length = self::FIELD_TYPE_LENGTH_MAP[$fieldType] ?? null;
            $primaryKey = $field->isKeyField() || ($fieldType == Field::TYPE_ID);
            $autoIncrement = ($fieldType == Field::TYPE_ID);

            if ($field instanceof DatasourceUpdateField) {
                $columns[] = new UpdatableTableColumn($field->getName(), $type, $length, null, null, $primaryKey, $autoIncrement, false, $field->getOriginalName());
            } else {
                $columns[] = new TableColumn($field->getName(), $type, $length, null, null, $primaryKey, $autoIncrement);
            }
        }


        $newMetaData = new TableMetaData($this->getConfig()->getTableName(), $columns);


        // Check to see whether the table already exists
        $sql = "";
        $databaseConnection = $this->returnDatabaseConnection();
        try {
            $previousMetaData = $this->dbConnection->getTableMetaData($this->getConfig()->getTableName());
            $sql = $this->tableDDLGenerator->generateTableModifySQL($previousMetaData, $newMetaData, $databaseConnection);
        } catch (\Exception $e) {
            $sql = $this->tableDDLGenerator->generateTableCreateSQL($newMetaData, $databaseConnection);
        }


        if (trim($sql))
            $databaseConnection->executeScript($sql);


    }


}
