<?php


namespace Kinintel\Objects\Datasource\SQLDatabase;


use Exception;
use Kinikit\Core\DependencyInjection\Container;
use Kinikit\Core\Exception\DebugException;
use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Template\TemplateParser;
use Kinikit\Core\Util\ObjectArrayUtils;
use Kinikit\Core\Validation\Validator;
use Kinikit\Persistence\Database\Connection\DatabaseConnection;
use Kinikit\Persistence\Database\Exception\SQLException;
use Kinikit\Persistence\Database\Generator\TableDDLGenerator;
use Kinikit\Persistence\Database\MetaData\TableIndex;
use Kinikit\Persistence\Database\MetaData\TableMetaData;
use Kinikit\Persistence\Database\MetaData\UpdatableTableColumn;
use Kinintel\Exception\DatasourceNotUpdatableException;
use Kinintel\Exception\DatasourceUpdateException;
use Kinintel\Exception\DuplicateEntriesException;
use Kinintel\Objects\Dataset\Dataset;
use Kinintel\Objects\Dataset\Tabular\ArrayTabularDataset;
use Kinintel\Objects\Dataset\Tabular\SQLResultSetTabularDataset;
use Kinintel\Objects\Dataset\Tabular\TabularDataset;
use Kinintel\Objects\Datasource\BaseDatasource;
use Kinintel\Objects\Datasource\BaseUpdatableDatasource;
use Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor\SQLTransformationProcessor;
use Kinintel\Objects\Datasource\SQLDatabase\Util\SQLColumnFieldMapper;
use Kinintel\Objects\Datasource\SQLDatabase\Util\SQLFilterJunctionEvaluator;
use Kinintel\Objects\Datasource\UpdatableDatasource;
use Kinintel\Services\Util\ParameterisedStringEvaluator;
use Kinintel\ValueObjects\Authentication\AuthenticationCredentials;
use Kinintel\ValueObjects\Authentication\SQLDatabase\MySQLAuthenticationCredentials;
use Kinintel\ValueObjects\Authentication\SQLDatabase\PostgreSQLAuthenticationCredentials;
use Kinintel\ValueObjects\Authentication\SQLDatabase\SQLiteAuthenticationCredentials;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\ManagedTableSQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Datasource\DatasourceUpdateConfig;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateField;
use Kinintel\ValueObjects\Transformation\Columns\ColumnsTransformation;
use Kinintel\ValueObjects\Transformation\Combine\CombineTransformation;
use Kinintel\ValueObjects\Transformation\Filter\FilterJunction;
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
     * @var SQLColumnFieldMapper
     */
    private $sqlColumnFieldMapper;


    /**
     * @var string[]
     */
    private static $additionalCredentialClasses = [];


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
        $this->sqlColumnFieldMapper = new SQLColumnFieldMapper();
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
            MySQLAuthenticationCredentials::class,
            PostgreSQLAuthenticationCredentials::class
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
            FormulaTransformation::class,
            CombineTransformation::class
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

        $authenticationCredentials = $this->getAuthenticationCredentials();
        $resultSet = $authenticationCredentials->query($query->getSQL(), $query->getParameters());

        // Grab columns
        $columns = $this->getConfig()->returnEvaluatedColumns($parameterValues);


        // If no explicit columns and table based query with no column changing transformations
        // Generate explicit columns to allow for dataset update.
        if ($this->getConfig()->getSource() == "table" && !$columns && $this->hasOriginalColumns) {

            $columns = [];
            $tableMetaData = $dbConnection->getTableMetaData($this->getConfig()->getTableName());

            foreach ($tableMetaData->getColumns() as $column) {
                $columns[] = $this->sqlColumnFieldMapper->mapResultSetColumnToField($column);
            }
        }


        // Return a tabular dataset
        $result = new SQLResultSetTabularDataset($resultSet, $columns);

        return $result;

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

        do {

            // Get all data from the dataset
            $allData = $dataset->nextNDataItems(50);

            // Update mapped field data
            $insertDataset = $this->updateMappedFieldData(new ArrayTabularDataset($dataset->getColumns(), $allData), $updateMode);

            // Only continue if some data to process
            if (sizeof($allData) > 0) {

                // Get insert columns from dataset
                $updateColumns = null;
                if ($insertDataset->getColumns()) {
                    if ($updateMode == UpdatableDatasource::UPDATE_MODE_DELETE) {
                        $updateColumns = [];
                        foreach ($insertDataset->getColumns() as $column) {
                            if ($column->isKeyField()) {
                                $updateColumns[] = $column->getName();
                            }
                        }

                        // Fall back to all columns if no key field identified
                        if (!sizeof($updateColumns))
                            $updateColumns = ObjectArrayUtils::getMemberValueArrayForObjects("name", $insertDataset->getColumns());
                    } else {
                        $updateColumns = ObjectArrayUtils::getMemberValueArrayForObjects("name", $insertDataset->getColumns());
                    }
                }

                // Ensure we don't exceed the batch size.
                if ($updateColumns) {
                    $batchSize = 50 / ceil(sizeof($updateColumns) / 15);
                    $bulkDataManager->setBatchSize($batchSize);
                }


                try {
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
                } catch (SQLException $e) {
                    // There are multiple errors with code 23000 and they relate to integrity constraints
                    // https://dev.mysql.com/doc/connector-j/en/connector-j-reference-error-sqlstates.html
                    if ($e->getSqlStateCode() >= 23000 && $e->getSqlStateCode() <= 24000) {
                        if (str_contains(strtolower($e->getMessage()), "dup")){
                            throw new DuplicateEntriesException();
                        } else {
                            throw new DatasourceUpdateException("Error updating the datasource: A row had a null primary key or other uniqueness violation.");
                        }
                    } else {
                        Logger::log("SQL Error: " . $e->getMessage());
                        throw new DebugException(
                            message: "An unexpected error occurred updating the datasource",
                            debugMessage: "SQL Error " . $e->getMessage()
                        );
                    }
                }

            }
        } while (sizeof($allData) == 50);
    }

    /**
     * Delete multiple items from this datasource using a filter junction
     *
     * @param FilterJunction $filterJunction
     * @return null
     */
    public function filteredDelete($filterJunction) {

        /**
         * @var SQLDatabaseDatasourceConfig $config
         */
        $config = $this->getConfig();

        if ($config->getSource() !== SQLDatabaseDatasourceConfig::SOURCE_TABLE) {
            throw new DatasourceUpdateException("Attempted to delete from a SQL datasource which does not have a table source");
        }

        // Grab the database connection in use
        $databaseConnection = $this->getAuthenticationCredentials()->returnDatabaseConnection();

        // Create a junction evaluator
        $sqlJunctionEvaluator = new SQLFilterJunctionEvaluator(null, null, $databaseConnection);

        // Grab the table and where clause
        $table = $config->getTableName();
        $whereClause = $sqlJunctionEvaluator->evaluateFilterJunctionSQL($filterJunction);

        // Construct delete sql and execute
        $sql = "DELETE FROM $table";
        if ($whereClause['sql'] ?? null)
            $sql .= " WHERE {$whereClause['sql']}";

        $databaseConnection->execute($sql, $whereClause["parameters"]);


    }


    /**
     * Event method called when a parent datasource instance is saved to provide
     * an opportunity to update e.g. structural stuff based on updated config.
     *
     * The default behaviour here is to update fields
     *
     */
    public function onInstanceSave() {
        if ($this->getConfig()->isManageTableStructure())
            $this->updateFields($this->getConfig()->getColumns());
    }


    /**
     * Handle instance delete when a parent datasource instance is deleted.
     *
     * @return mixed|void
     */
    public function onInstanceDelete() {

        // Only proceed if we are managing table structure
        if ($this->getConfig()->isManageTableStructure()) {
            $databaseConnection = $this->returnDatabaseConnection();
            $dropSQL = $this->tableDDLGenerator->generateTableDropSQL($this->getConfig()->getTableName());
            $databaseConnection->executeScript($dropSQL);
        }
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

            if ($config->isPagingViaParameters()) {
                if (end($this->transformations) instanceof PagingTransformation) {

                    /**
                     * @var PagingTransformation $pagingTransformation
                     */
                    $pagingTransformation = array_pop($this->transformations);
                    $model = ["limit" => $pagingTransformation->getLimit(), "offset" => $pagingTransformation->getOffset()];
                    $parameterValues = array_merge($parameterValues, $model);

                }

            }

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
            $column = $this->sqlColumnFieldMapper->mapFieldToTableColumn($field);
            if ($field instanceof DatasourceUpdateField) {
                $columns[] = new UpdatableTableColumn($column->getName(), $column->getType(), $column->getLength(), $column->getPrecision(), $column->getDefaultValue(), $column->isPrimaryKey(), $column->isAutoIncrement(), $column->isNotNull(), $field->getPreviousName());
            } else {
                $columns[] = $column;
            }
        }

        $indexes = [];
        // If we have a managed table structure, also check for indexes
        if ($this->getConfig() instanceof ManagedTableSQLDatabaseDatasourceConfig) {
            // Index all fields by name
            $indexedFields = ObjectArrayUtils::indexArrayOfObjectsByMember("name", $fields);

            // Loop through and map to table index objects
            foreach ($this->getConfig()->getIndexes() as $index) {
                $indexFields = $index->getFieldNames();
                $indexColumns = [];
                foreach ($indexFields as $indexField) {
                    $matchingField = $indexedFields[$indexField] ?? null;
                    if ($matchingField)
                        $indexColumns[] = $this->sqlColumnFieldMapper->mapFieldToIndexColumn($matchingField);
                    else
                        throw new DatasourceUpdateException("You attempted to remove a field which is referenced in an index");

                }
                $indexes[] = new TableIndex(md5(join("", $indexFields)), $indexColumns);
            }
        }

        $newMetaData = new TableMetaData($this->getConfig()->getTableName(), $columns, $indexes);

        // Check to see whether the table already exists
        $sql = "";
        $databaseConnection = $this->returnDatabaseConnection();
        try {
            $previousMetaData = $this->dbConnection->getTableMetaData($this->getConfig()->getTableName());
            $sql = $this->tableDDLGenerator->generateTableModifySQL($previousMetaData, $newMetaData, $databaseConnection);
        } catch (\Exception $e) {
            $sql = $this->tableDDLGenerator->generateTableCreateSQL($newMetaData, $databaseConnection);
        }

        if (trim($sql ?? ""))
            $databaseConnection->executeScript($sql);

    }


}
