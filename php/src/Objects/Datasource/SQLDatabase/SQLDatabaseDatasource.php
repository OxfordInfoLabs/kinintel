<?php


namespace Kinintel\Objects\Datasource\SQLDatabase;


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
use Kinintel\Objects\Datasource\DatasourceDataValidator;
use Kinintel\Objects\Datasource\SQLDatabase\TransformationProcessor\SQLTransformationProcessor;
use Kinintel\Objects\Datasource\SQLDatabase\Util\SQLColumnFieldMapper;
use Kinintel\Objects\Datasource\SQLDatabase\Util\SQLFilterJunctionEvaluator;
use Kinintel\Objects\Datasource\UpdatableDatasource;
use Kinintel\Services\Hook\DatasourceHookService;
use Kinintel\Services\Util\ParameterisedStringEvaluator;
use Kinintel\ValueObjects\Authentication\AuthenticationCredentials;
use Kinintel\ValueObjects\Authentication\SQLDatabase\MySQLAuthenticationCredentials;
use Kinintel\ValueObjects\Authentication\SQLDatabase\PostgreSQLAuthenticationCredentials;
use Kinintel\ValueObjects\Authentication\SQLDatabase\SQLiteAuthenticationCredentials;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\ManagedTableSQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Datasource\Configuration\SQLDatabase\SQLDatabaseDatasourceConfig;
use Kinintel\ValueObjects\Datasource\DatasourceUpdateConfig;
use Kinintel\ValueObjects\Datasource\SQLDatabase\SQLQuery;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateField;
use Kinintel\ValueObjects\Datasource\Update\DatasourceUpdateResult;
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
use PDOException;

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
     * @var DatasourceHookService
     */
    private $datasourceHookService;


    /**
     * Cached DB connection for efficiency
     *
     * @var DatabaseConnection
     */
    private $dbConnection = null;


    protected TableDDLGenerator $tableDDLGenerator;
    private SQLColumnFieldMapper $sqlColumnFieldMapper;


    /**
     * @var string[]
     */
    private static $additionalCredentialClasses = [];


    // Read batch size for reading records for update.
    const READ_BATCH_SIZE = 1000;

    // Update batch size (used to configure batching inserters etfc)
    const UPDATE_BATCH_SIZE = 50;


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
        $this->datasourceHookService = Container::instance()->get(DatasourceHookService::class);
    }

    /**
     * Testing set method
     *
     * @param DatasourceHookService|object $datasourceHookService
     */
    public function setDatasourceHookService(DatasourceHookService $datasourceHookService): void {
        $this->datasourceHookService = $datasourceHookService;
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

            // Negate original columns if a non filter / sort transformation encountered.
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


        Logger::log($query->getParameters(), 6);
        Logger::log($query->getSQL(), 6);

        $authenticationCredentials = $this->getAuthenticationCredentials();
        try {
            $resultSet = $authenticationCredentials->query($query->getSQL(), $query->getParameters());
        } catch (PDOException $e) {
            $time = microtime(true);
            Logger::log("SQL Database Datasource Error: " . $time);
            Logger::log($e);
            throw new DebugException("Database Error occured when running query, see logs " . $time, debugMessage: $e->getMessage());
        }

        $fields = $this->returnFields($parameterValues);

        // Return a tabular dataset
        $result = new SQLResultSetTabularDataset($resultSet, $fields);

        return $result;

    }

    /**
     * @param $parameterValues
     * @param bool|Transformation $deriveUpToTransformation If false, we don't derive columns. If true, we derive based on all transformations. If given a transformation, we derive up to the transformation before.
     * @return Field[]
     */
    public function returnFields($parameterValues, bool|Transformation $deriveUpToTransformation = false): array {
        $dbConnection = $this->returnDatabaseConnection();

        // Get columns from the datasource config if we are using original columns
        $originalFields = $this->getConfig()->returnEvaluatedColumns($parameterValues);


        // If there aren't explicit columns and it's a table based datasource with no column changing transformations
        // Generate explicit columns to allow for dataset update.
        if ($this->getConfig()->getSource() == "table" && !$originalFields && ($this->hasOriginalColumns || $deriveUpToTransformation)) {

            $tableMetaData = $dbConnection->getTableMetaData($this->getConfig()->getTableName());

            $originalFields = array_values(array_map(
                fn($resultSetColumn) => $this->sqlColumnFieldMapper->mapResultSetColumnToField($resultSetColumn),
                $tableMetaData->getColumns()
            ));
        }


        if ($this->hasOriginalColumns || ($deriveUpToTransformation === false)) return $originalFields;


        $fields = $originalFields;
        foreach ($this->transformations as $transformation) {

            /** @var SQLDatabaseTransformation $transformation */

            // Only derive until we get to the target transformation
            if ($transformation === $deriveUpToTransformation) break;


            $fields = $transformation->returnAlteredColumns($fields);

        }


        return $fields;
    }


    /**
     * Update this datasource using a supplied dataset and the update mode supplied.
     *
     * @param Dataset $dataset
     * @param string $updateMode
     *
     * @return DatasourceUpdateResult
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

        $changed = 0;
        $ignored = 0;
        $validationErrors = [];

        // Update columns to match those configured on this datasource
        $datasetColumns = ObjectArrayUtils::indexArrayOfObjectsByMember("name", $dataset->getColumns() ?? []);
        $datasourceColumns = ObjectArrayUtils::indexArrayOfObjectsByMember("name", $this->getConfig()->getColumns() ?? []);
        $columns = array_values(array_merge($datasetColumns, $datasourceColumns));
        $dataset->setColumns($columns);

        // Data validator
        $datasourceDataValidator = new DatasourceDataValidator($columns);

        do {

            // Get all data from the dataset
            $allData = $dataset->nextNDataItems(self::READ_BATCH_SIZE);


            // Update mapped field data
            $insertDataset = $this->updateMappedFieldData(new ArrayTabularDataset($columns, $allData), $updateMode);

            // Only continue if some data to process
            if (sizeof($allData) > 0) {

                // Get insert columns from dataset
                $updateColumns = null;
                if ($insertDataset->getColumns()) {
                    if ($updateMode == UpdatableDatasource::UPDATE_MODE_DELETE) {
                        $updateColumns = [];
                        foreach ($insertDataset->getColumns() as $column) {
                            if ($column->isKeyField() || $column->getType() == Field::TYPE_ID) {
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
                    $batchSize = self::UPDATE_BATCH_SIZE / ceil(sizeof($updateColumns) / 15);
                    $bulkDataManager->setBatchSize($batchSize);
                }


                // Validate the data before insert.
                $newValidationErrors = $datasourceDataValidator->validateUpdateData($allData, $updateMode, true);
                $validationErrors = array_merge($validationErrors, $newValidationErrors);

                if (sizeof($validationErrors) == 0) {

                    try {
                        switch ($updateMode) {
                            case UpdatableDatasource::UPDATE_MODE_ADD:
                                $bulkDataManager->insert($config->getTableName(), $allData, $updateColumns, true);
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

                        $changed += sizeof($allData);

                        // Run any hooks using hook service if instance info has been proviced
                        if ($this->getInstanceInfo())
                            $this->datasourceHookService->processHooks($this->getInstanceInfo()->getKey(), $updateMode, $allData);


                    } catch (SQLException $e) {
                        // There are multiple errors with code 23000 and they relate to integrity constraints
                        // https://dev.mysql.com/doc/connector-j/en/connector-j-reference-error-sqlstates.html
                        if ($e->getSqlStateCode() >= 23000 && $e->getSqlStateCode() <= 24000) {
                            if (str_contains(strtolower($e->getMessage()), "dup")) {
                                throw new DuplicateEntriesException();
                            } else {
                                Logger::log("SQL Error writing to table (uniqueness violation). Table name: " . $config->getTableName() . " DATA:");
                                Logger::log($allData, 6);
                                Logger::log($e, 6);
                                throw new DatasourceUpdateException("Error updating the datasource: A row had a null primary key or other uniqueness violation.");
                            }
                        } else {
                            $debugMessage = "SQL Error: " . $e->getMessage() . "\n with tableName " . $config->getTableName();
                            Logger::log($debugMessage, 4);
                            throw new DebugException(
                                message: "An unexpected error occurred updating the datasource",
                                debugMessage: $debugMessage
                            );
                        }
                    }
                } else {
                    $ignored += sizeof($allData);
                }

            }
        } while (sizeof($allData) == self::READ_BATCH_SIZE);

        return new DatasourceUpdateResult($updateMode == UpdatableDatasource::UPDATE_MODE_ADD ? $changed : 0,
            $updateMode == UpdatableDatasource::UPDATE_MODE_UPDATE ? $changed : 0,
            $updateMode == UpdatableDatasource::UPDATE_MODE_REPLACE ? $changed : 0,
            $updateMode == UpdatableDatasource::UPDATE_MODE_DELETE ? $changed : 0,
            sizeof($validationErrors), $ignored,
            sizeof($validationErrors) ? [$updateMode => $validationErrors] : []);


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

        /** @var SQLDatabaseDatasourceConfig $config */
        $config = $this->getConfig();

        $parameterisedStringEvaluator = Container::instance()->get(ParameterisedStringEvaluator::class);

        // If a tabular based source, create base clause
        if ($config->getSource() == SQLDatabaseDatasourceConfig::SOURCE_TABLE) {
            $tableName = $parameterisedStringEvaluator->evaluateString($config->getTableName(), [], $parameterValues);
            $query = new SQLQuery("*", $tableName);
        } else {
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


        // Process each transformation
        foreach ($this->transformations as $transformation) {
            /* @var $transformation SQLDatabaseTransformation */
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
                $indexes[] = new TableIndex("idx_" . md5(join("", $indexFields)), $indexColumns);
            }
        }

        $newMetaData = new TableMetaData($this->getConfig()->getTableName(), $columns, $indexes);

        // Check to see whether the table already exists
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
